<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PresentsPublicCatalog;
use App\Models\Actor;
use App\Models\Video;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Inertia\Inertia;
use Inertia\Response;

class PublicCatalogController extends Controller
{
    use PresentsPublicCatalog;

    public function index(): Response
    {
        return Inertia::render('catalog/index', [
            'channels' => YoutubeChannel::query()
                ->where('is_public', true)
                ->withCount('videos')
                ->orderBy('title')
                ->get()
                ->map(fn (YoutubeChannel $channel): array => $this->channelSummary($channel))
                ->all(),
            'videos' => YoutubeVideo::query()
                ->where('is_public', true)
                ->with('channel')
                ->orderByDesc('published_at')
                ->limit(50)
                ->get()
                ->map(fn (YoutubeVideo $video): array => $this->videoSummary($video))
                ->all(),
            'actors' => Actor::query()
                ->active()
                ->visible()
                ->orderBy('name')
                ->limit(100)
                ->get()
                ->map(fn (Actor $actor): array => $this->actorSummary($actor))
                ->all(),
        ]);
    }

    public function channel(YoutubeChannel $channel): Response
    {
        abort_unless($channel->is_public, 404);

        $channel->loadCount([
            'videos' => fn ($query) => $query->where('is_public', true),
        ])->load([
            'videos' => fn ($query) => $query->where('is_public', true)->with('channel')->orderByDesc('published_at'),
        ]);

        return Inertia::render('catalog/channel', [
            'channel' => $this->channelDetail($channel),
        ]);
    }

    public function video(Video $video): Response
    {
        abort_unless($video->is_public, 404);

        $video->load([
            'channel',
            'actors' => fn ($query) => $query->active()->visible()->orderBy('name'),
        ]);

        return Inertia::render('catalog/video', [
            'video' => $this->videoDetail($video),
        ]);
    }

    public function actor(Actor $actor): Response
    {
        abort_if($actor->isMerged() || ! $actor->is_public, 404);

        $actor->load([
            'referenceUrls',
            'videos' => fn ($query) => $query->where('is_public', true)->with('channel')->orderByDesc('published_at'),
        ]);

        return Inertia::render('catalog/actor', [
            'actor' => $this->actorDetail($actor),
        ]);
    }
}
