<?php

namespace App\Services\ChannelRequest;

use App\Jobs\CollectChannelVideos;
use App\Models\ChannelRequest;
use App\Models\User;
use App\Support\CatalogOwner;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Cable8mm\Youtube\Sync\ChannelInputResolver;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChannelRequestManager
{
    /**
     * @throws RuntimeException
     */
    public function approve(ChannelRequest $request, User $reviewer, ?string $note = null): YoutubeChannel
    {
        if ($request->status !== ChannelRequest::STATUS_PENDING) {
            throw new RuntimeException('Only pending channel requests can be approved.');
        }

        return DB::transaction(function () use ($request, $reviewer, $note): YoutubeChannel {
            $resolved = app(ChannelInputResolver::class)->resolve($request->input);

            if (! is_object($resolved)) {
                throw new RuntimeException('The channel request could not be resolved.');
            }

            $channel = YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::updateOrCreate(
                ['channel_id' => $resolved->id],
                [
                    'handle' => $resolved->snippet->customUrl ?? null,
                    'title' => $resolved->snippet->title ?? $resolved->id,
                    'uploads_playlist_id' => $resolved->contentDetails->relatedPlaylists->uploads ?? null,
                    'is_active' => true,
                ],
            ));

            $channel->user_id = CatalogOwner::id();
            $channel->is_public = true;
            $channel->save();

            YoutubeVideo::query()
                ->where('channelid', $channel->channel_id)
                ->whereNull('youtube_channel_id')
                ->update(['youtube_channel_id' => $channel->id]);

            $request->update([
                'status' => ChannelRequest::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            CollectChannelVideos::dispatch($channel->id);

            return $channel;
        });
    }

    /**
     * @throws RuntimeException
     */
    public function reject(ChannelRequest $request, User $reviewer, ?string $note = null): void
    {
        if ($request->status !== ChannelRequest::STATUS_PENDING) {
            throw new RuntimeException('Only pending channel requests can be rejected.');
        }

        $request->update([
            'status' => ChannelRequest::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);
    }
}
