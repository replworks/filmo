<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Actor;
use App\Models\Video;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;

trait PresentsPublicCatalog
{
    /**
     * @return array<string, mixed>
     */
    protected function actorSummary(Actor $actor): array
    {
        return [
            'id' => $actor->id,
            'name' => $actor->name,
            'activity_name' => $actor->activity_name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function videoSummary(YoutubeVideo $video): array
    {
        return [
            'id' => $video->id,
            'title' => $video->title,
            'published_at' => $video->published_at?->toIso8601String(),
            'youtube_url' => $this->videoUrl($video),
            'channel' => $video->channel !== null
                ? ['id' => $video->channel->id, 'title' => $video->channel->title]
                : ($video->channel_title === null
                    ? null
                    : ['id' => null, 'title' => $video->channel_title]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function channelSummary(YoutubeChannel $channel): array
    {
        return [
            'id' => $channel->id,
            'title' => $channel->title,
            'handle' => $channel->handle,
            'video_count' => $channel->videos_count ?? $channel->videos()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function channelDetail(YoutubeChannel $channel): array
    {
        return [
            ...$this->channelSummary($channel),
            'videos' => $channel->videos->map(fn (YoutubeVideo $video): array => $this->videoSummary($video))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function videoDetail(Video $video): array
    {
        return [
            ...$this->videoSummary($video),
            'actors' => $video->actors->map(fn (Actor $actor): array => $this->actorSummary($actor))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function actorDetail(Actor $actor): array
    {
        return [
            ...$this->actorSummary($actor),
            'birth_date' => $actor->birth_date?->toDateString(),
            'birth_place' => $actor->birth_place,
            'education' => $actor->education,
            'debut' => $actor->debut,
            'agency' => $actor->agency,
            'nationality' => $actor->nationality,
            'reference_urls' => $actor->referenceUrls->pluck('url')->all(),
            'videos' => $actor->videos
                ->map(fn (YoutubeVideo $video): array => $this->videoSummary($video))
                ->all(),
        ];
    }

    protected function videoUrl(YoutubeVideo $video): string
    {
        return 'https://www.youtube.com/watch?v='.$video->video_id;
    }
}
