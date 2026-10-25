<?php

namespace App\Console\Commands;

use Cable8mm\Youtube\Sync\ChannelInputResolver;
use Cable8mm\Youtube\Sync\FailureReason;
use Cable8mm\Youtube\Youtube;
use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Throwable;

class ProbeYouTubeCommand extends Command
{
    /**
     * The parts requested from videos.list, matching what collection needs.
     */
    public const VIDEO_PART = ['id', 'snippet', 'contentDetails', 'statistics', 'status'];

    public const PAGE_SIZE = 50;

    public const VIDEO_SAMPLE_SIZE = 3;

    /**
     * A well-formed Video ID that is not expected to exist.
     */
    public const MISSING_VIDEO_ID = 'AAAAAAAAAAA';

    protected $signature = 'filmo:probe-youtube
        {--channel=* : Channel ID, @handle, or Channel URL to resolve}
        {--pages=2 : Maximum uploads pages to read per resolved Channel}
        {--output=docs/observations/t-002-youtube.json : Path of the JSON observation}';

    protected $description = 'Observe live YouTube Data API responses for Channel resolution and Video collection';

    public function handle(): int
    {
        $key = (string) config('youtube.key');

        if ($key === '' || $key === 'YOUR_API_KEY') {
            $this->error('YOUTUBE_API_KEY is not configured.');

            return self::FAILURE;
        }

        $inputs = array_values(array_filter(
            array_map(fn (mixed $input): string => trim((string) $input), $this->option('channel')),
            fn (string $input): bool => $input !== '',
        ));

        if ($inputs === []) {
            $this->error('Pass at least one --channel reference.');

            return self::FAILURE;
        }

        $youtube = app(Youtube::class);
        $maxPages = max(1, (int) $this->option('pages'));
        $quotaExhausted = false;

        $observation = [
            'probe' => 'filmo:probe-youtube',
            'ran_at' => Carbon::now()->toIso8601String(),
            'environment' => $this->environment($key),
            'page_size' => self::PAGE_SIZE,
            'references' => [],
            'pages' => [],
            'videos' => [],
            'missing_video' => null,
        ];

        $playlists = [];

        foreach ($inputs as $input) {
            $reference = $this->resolveReference($youtube, $input);
            $observation['references'][] = $reference;

            $playlistId = $reference['channel']['uploads_playlist_id'] ?? null;

            if (is_string($playlistId) && $playlistId !== '') {
                $playlists[$playlistId] = true;
            }

            $quotaExhausted = $quotaExhausted || $this->isQuotaExhausted($reference['failure']);
        }

        foreach (array_keys($playlists) as $playlistId) {
            $pages = $this->readPages($youtube, $playlistId, $maxPages, $quotaExhausted);
            $observation['pages'] = array_merge($observation['pages'], $pages);

            if ($pages !== []) {
                $lastPage = $pages[array_key_last($pages)];
                $quotaExhausted = $quotaExhausted || $this->isQuotaExhausted($lastPage['failure'] ?? null);
            }
        }

        $videos = $quotaExhausted ? [] : $this->readVideos($youtube, $observation['pages']);
        $observation['videos'] = $videos;

        if ($videos !== []) {
            $quotaExhausted = $quotaExhausted || $this->isQuotaExhausted($videos[0]['failure'] ?? null);
        }

        if (! $quotaExhausted && $observation['pages'] !== []) {
            $observation['missing_video'] = $this->readMissingVideo($youtube);
        }

        $path = $this->writeObservation($observation);

        $this->summarize($observation, $path);

        return self::SUCCESS;
    }

    /**
     * Resolve one Channel reference and record its identity.
     *
     * @return array<string, mixed>
     */
    protected function resolveReference(Youtube $youtube, string $input): array
    {
        $lookup = ChannelInputResolver::lookup($input);
        $reference = [
            'input' => $input,
            'lookup' => $lookup === null ? null : ['method' => $lookup[0], 'value' => $lookup[1]],
            'outcome' => 'unsupported',
            'channel' => null,
            'failure' => null,
        ];

        try {
            $resolved = (new ChannelInputResolver($youtube))->resolve($input);
        } catch (Throwable $e) {
            $reference['outcome'] = 'error';
            $reference['failure'] = $this->describeFailure($e);

            return $reference;
        }

        if ($resolved === ChannelInputResolver::UNSUPPORTED) {
            return $reference;
        }

        if ($resolved === ChannelInputResolver::NOT_FOUND) {
            $reference['outcome'] = 'not_found';

            return $reference;
        }

        $reference['outcome'] = 'resolved';
        $reference['channel'] = [
            'id' => $resolved->id ?? null,
            'title' => $resolved->snippet->title ?? null,
            'custom_url' => $resolved->snippet->customUrl ?? null,
            'published_at' => $resolved->snippet->publishedAt ?? null,
            'uploads_playlist_id' => $resolved->contentDetails->relatedPlaylists->uploads ?? null,
            'subscriber_count' => $resolved->statistics->subscriberCount ?? null,
            'hidden_subscriber_count' => $resolved->statistics->hiddenSubscriberCount ?? null,
            'video_count' => $resolved->statistics->videoCount ?? null,
            'view_count' => $resolved->statistics->viewCount ?? null,
        ];

        return $reference;
    }

    /**
     * Read uploads pages for one playlist. A failed page keeps the pages already read.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function readPages(Youtube $youtube, string $playlistId, int $maxPages, bool $quotaExhausted): array
    {
        $pages = [];
        $token = '';

        for ($number = 1; $number <= $maxPages; $number++) {
            if ($quotaExhausted) {
                break;
            }

            $requestedToken = $token;

            try {
                $page = $youtube->getPlaylistItemsByPlaylistId($playlistId, $requestedToken, self::PAGE_SIZE);
            } catch (Throwable $e) {
                $pages[] = $this->page($playlistId, $number, $requestedToken, 'error', failure: $this->describeFailure($e));

                break;
            }

            $items = $this->playlistItems($page['results'] ?: []);
            $token = (string) ($page['info']['nextPageToken'] ?: '');

            $pages[] = $this->page(
                $playlistId,
                $number,
                $requestedToken,
                'ok',
                count($items),
                (int) ($page['info']['totalResults'] ?? 0),
                $token,
                $items,
            );

            if ($token === '') {
                break;
            }
        }

        return $pages;
    }

    /**
     * @param  array<int, mixed>  $results
     * @return array<int, array<string, mixed>>
     */
    protected function playlistItems(array $results): array
    {
        $items = [];

        foreach ($results as $item) {
            $items[] = [
                'playlist_item_id' => $item->id ?? null,
                'video_id' => $item->contentDetails->videoId ?? null,
                'title' => $item->snippet->title ?? null,
                'channel_title' => $item->snippet->channelTitle ?? null,
                'playlist_published_at' => $item->snippet->publishedAt ?? null,
                'video_published_at' => $item->contentDetails->videoPublishedAt ?? null,
                'privacy_status' => $item->status->privacyStatus ?? null,
                'thumbnail_url' => $item->snippet->thumbnails->high->url ?? $item->snippet->thumbnails->default->url ?? null,
            ];
        }

        return $items;
    }

    /**
     * @param  array<int, array<string, mixed>>  $pages
     * @return array<int, array<string, mixed>>
     */
    protected function readVideos(Youtube $youtube, array $pages): array
    {
        $videoIds = [];

        foreach ($pages as $page) {
            foreach ($page['items'] ?? [] as $item) {
                if (is_string($item['video_id']) && $item['video_id'] !== '') {
                    $videoIds[$item['video_id']] = true;
                }
            }
        }

        $sample = array_slice(array_keys($videoIds), 0, self::VIDEO_SAMPLE_SIZE);

        if ($sample === []) {
            return [];
        }

        try {
            $videos = $youtube->getVideoInfo($sample, self::VIDEO_PART) ?: [];
        } catch (Throwable $e) {
            return [['requested_ids' => $sample, 'outcome' => 'error', 'failure' => $this->describeFailure($e)]];
        }

        $observed = [];

        foreach ($videos as $video) {
            $observed[] = [
                'requested_ids' => $sample,
                'outcome' => 'ok',
                'failure' => null,
                'id' => $video->id ?? null,
                'title' => $video->snippet->title ?? null,
                'description_length' => isset($video->snippet->description) ? mb_strlen($video->snippet->description) : null,
                'published_at' => $video->snippet->publishedAt ?? null,
                'duration' => $video->contentDetails->duration ?? null,
                'definition' => $video->contentDetails->definition ?? null,
                'caption' => $video->contentDetails->caption ?? null,
                'licensed_content' => $video->contentDetails->licensedContent ?? null,
                'view_count' => $video->statistics->viewCount ?? null,
                'like_count' => $video->statistics->likeCount ?? null,
                'comment_count' => $video->statistics->commentCount ?? null,
                'privacy_status' => $video->status->privacyStatus ?? null,
                'upload_status' => $video->status->uploadStatus ?? null,
                'embeddable' => $video->status->embeddable ?? null,
                'made_for_kids' => $video->status->madeForKids ?? null,
                'thumbnail_url' => $video->snippet->thumbnails->high->url ?? $video->snippet->thumbnails->default->url ?? null,
            ];
        }

        return $observed === [] ? [['requested_ids' => $sample, 'outcome' => 'empty', 'failure' => null]] : $observed;
    }

    /**
     * @return array<string, mixed>
     */
    protected function readMissingVideo(Youtube $youtube): array
    {
        $requested = [self::MISSING_VIDEO_ID];

        try {
            $videos = $youtube->getVideoInfo($requested, self::VIDEO_PART) ?: [];
        } catch (Throwable $e) {
            return ['requested_ids' => $requested, 'returned_count' => null, 'failure' => $this->describeFailure($e)];
        }

        return [
            'requested_ids' => $requested,
            'returned_count' => count($videos),
            'returned_ids' => array_values(array_map(fn (object $video): ?string => $video->id ?? null, $videos)),
            'failure' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function page(
        string $playlistId,
        int $number,
        string $requestedToken,
        string $outcome,
        int $itemCount = 0,
        int $totalResults = 0,
        string $nextToken = '',
        array $items = [],
        ?array $failure = null,
    ): array {
        return [
            'playlist_id' => $playlistId,
            'page' => $number,
            'requested_page_token' => $requestedToken === '' ? null : $requestedToken,
            'outcome' => $outcome,
            'item_count' => $itemCount,
            'total_results' => $totalResults,
            'next_page_token' => $nextToken === '' ? null : $nextToken,
            'items' => $items,
            'failure' => $failure,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function describeFailure(Throwable $e): array
    {
        return [
            'reason' => FailureReason::fromThrowable($e),
            'exception' => $e::class,
            'http_code' => $e->getCode() === 0 ? null : (int) $e->getCode(),
            'message' => $e->getMessage(),
        ];
    }

    protected function isQuotaExhausted(?array $failure): bool
    {
        return ($failure['reason'] ?? null) === FailureReason::QUOTA_EXCEEDED;
    }

    /**
     * @return array<string, mixed>
     */
    protected function environment(string $key): array
    {
        return [
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'youtube_package' => InstalledVersions::getPrettyVersion('cable8mm/youtube'),
            'api_key_fingerprint' => substr($key, 0, 4).'…('.strlen($key).' chars)',
        ];
    }

    protected function writeObservation(array $observation): string
    {
        $option = (string) $this->option('output');
        $path = str_starts_with($option, '/') ? $option : base_path($option);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($observation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL);

        return $path;
    }

    protected function summarize(array $observation, string $path): void
    {
        foreach ($observation['references'] as $reference) {
            $channel = $reference['channel'];
            $reason = $reference['failure']['reason'] ?? null;

            $this->line(sprintf(
                '%s -> %s%s',
                $reference['input'],
                $reference['outcome'],
                $channel === null ? ($reason === null ? '' : ' ('.$reason.')') : ' '.$channel['id'].' '.$channel['title'],
            ));
        }

        foreach ($observation['pages'] as $page) {
            $this->line(sprintf(
                'page %d of %s: %s, %d items, next token %s',
                $page['page'],
                $page['playlist_id'],
                $page['outcome'],
                $page['item_count'],
                $page['next_page_token'] === null ? 'absent' : 'present',
            ));
        }

        $this->line(sprintf('videos.list: %d observed', count($observation['videos'])));
        $this->line(sprintf('missing Video ID returned %s', $observation['missing_video']['returned_count'] ?? 'no response'));
        $this->info('Wrote '.$path);
    }
}
