<?php

namespace App\Jobs;

use Cable8mm\Youtube\Events\ChannelSynced;
use Cable8mm\Youtube\Events\ChannelSyncFailed;
use Cable8mm\Youtube\Events\QuotaExceeded;
use Cable8mm\Youtube\Exceptions\YoutubeApiException;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeSyncError;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Cable8mm\Youtube\Sync\FailureReason;
use Cable8mm\Youtube\Sync\QuotaBlock;
use Cable8mm\Youtube\Youtube;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Throwable;

/**
 * Collects the available Videos of one Channel, persisting each successful page.
 */
class CollectChannelVideos implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const CHANNEL_PART = ['id', 'snippet', 'contentDetails'];

    public const VIDEO_PART = ['id', 'snippet', 'contentDetails', 'statistics'];

    public const PAGE_SIZE = 50;

    public int $tries = 1;

    protected bool $channelNotFound = false;

    public function __construct(
        public int $channelId,
        public ?int $maxPages = null,
    ) {}

    public function handle(): void
    {
        $quota = new QuotaBlock;

        if ($quota->isActive()) {
            return;
        }

        $channel = YoutubeChannel::find($this->channelId);

        if ($channel === null) {
            return;
        }

        try {
            $this->collect($channel);
        } catch (Throwable $e) {
            try {
                $this->recordFailure($channel, $quota, $e);
            } catch (Throwable) {
                // Recording the failure must not let the job throw.
            }
        }
    }

    protected function collect(YoutubeChannel $channel): void
    {
        $youtube = app(Youtube::class);

        if (blank($channel->uploads_playlist_id)) {
            $this->resolveChannel($youtube, $channel);
        }

        $seen = [];
        $created = 0;
        $updated = 0;
        $pages = 0;
        $token = '';

        do {
            $page = $youtube->getPlaylistItemsByPlaylistId($channel->uploads_playlist_id, $token, self::PAGE_SIZE);
            $ids = [];

            foreach ($page['results'] ?: [] as $item) {
                $videoId = $item->contentDetails->videoId ?? null;

                if (is_string($videoId) && $videoId !== '') {
                    $ids[$videoId] = true;
                }
            }

            $ids = array_keys($ids);

            if ($ids !== []) {
                foreach ($youtube->getVideoInfo($ids, self::VIDEO_PART) ?: [] as $item) {
                    $this->storeVideo($channel, $item) ? $created++ : $updated++;
                    $seen[$item->id] = true;
                }
            }

            $token = (string) ($page['info']['nextPageToken'] ?? '');
            $pages++;
        } while ($token !== '' && ($this->maxPages === null || $pages < $this->maxPages));

        $unavailable = $this->maxPages === null ? $this->flagUnavailable($channel, $seen) : 0;

        $channel->update([
            'sync_status' => 'ok',
            'last_synced_at' => Carbon::now(),
            'last_error_reason' => null,
        ]);

        Event::dispatch(new ChannelSynced($channel, $created, $updated, $unavailable));
    }

    /**
     * Resolve the uploads playlist of a Channel that has none recorded.
     *
     * @throws YoutubeApiException
     */
    protected function resolveChannel(Youtube $youtube, YoutubeChannel $channel): void
    {
        $item = $youtube->getChannelById($channel->channel_id, [], self::CHANNEL_PART);

        if ($item === null) {
            $this->channelNotFound = true;

            throw new YoutubeApiException('Channel not found : '.$channel->channel_id, 0);
        }

        $channel->update([
            'uploads_playlist_id' => $item->contentDetails->relatedPlaylists->uploads ?? null,
            'handle' => $item->snippet->customUrl ?? $channel->handle,
            'title' => $item->snippet->title ?? $channel->title,
        ]);
    }

    /**
     * Create or update one Video Record. Returns true when it was created.
     */
    protected function storeVideo(YoutubeChannel $channel, object $item): bool
    {
        $video = YoutubeVideo::firstOrNew(['video_id' => $item->id]);
        $created = ! $video->exists;

        $video->fill([
            'youtube_channel_id' => $channel->id,
            'title' => $item->snippet->title ?? $item->id,
            'description' => $item->snippet->description ?? null,
            'published_at' => Carbon::parse($item->snippet->publishedAt),
            'duration' => $item->contentDetails->duration ?? null,
            'thumbnail_url' => $item->snippet->thumbnails->high->url ?? $item->snippet->thumbnails->default->url ?? null,
            'view_count' => $item->statistics->viewCount ?? null,
            'like_count' => $item->statistics->likeCount ?? null,
            'comment_count' => $item->statistics->commentCount ?? null,
            'unavailable_at' => null,
        ])->save();

        return $created;
    }

    /**
     * Mark Videos that a complete collection did not return as unavailable.
     *
     * @param  array<string, bool>  $seen
     */
    protected function flagUnavailable(YoutubeChannel $channel, array $seen): int
    {
        $missing = array_values(array_diff(
            $channel->videos()->pluck('video_id')->all(),
            array_keys($seen),
        ));

        $flagged = 0;

        foreach (array_chunk($missing, self::PAGE_SIZE) as $batch) {
            $flagged += $channel->videos()
                ->whereIn('video_id', $batch)
                ->whereNull('unavailable_at')
                ->update(['unavailable_at' => Carbon::now()]);
        }

        return $flagged;
    }

    protected function recordFailure(YoutubeChannel $channel, QuotaBlock $quota, Throwable $e): void
    {
        $reason = $this->channelNotFound
            ? FailureReason::CHANNEL_NOT_FOUND
            : FailureReason::fromThrowable($e);

        YoutubeSyncError::create([
            'youtube_channel_id' => $channel->id,
            'reason' => $reason,
            'http_code' => $e->getCode() === 0 ? null : (int) $e->getCode(),
            'message' => $e->getMessage(),
        ]);

        $channel->update([
            'sync_status' => $reason === FailureReason::QUOTA_EXCEEDED ? 'paused' : 'failed',
            'last_error_reason' => $reason,
        ]);

        if ($reason === FailureReason::QUOTA_EXCEEDED) {
            Event::dispatch(new QuotaExceeded($quota->start()));
        }

        Event::dispatch(new ChannelSyncFailed($channel, $reason, $e));
    }
}
