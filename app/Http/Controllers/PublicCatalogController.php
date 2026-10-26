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
                ->withCount('videos')
                ->orderBy('title')
                ->get()
                ->map(fn (YoutubeChannel $channel): array => $this->channelSummary($channel))
                ->all(),
            'videos' => YoutubeVideo::query()
                ->with('channel')
                ->orderByDesc('published_at')
                ->limit(50)
                ->get()
                ->map(fn (YoutubeVideo $video): array => $this->videoSummary($video))
                ->all(),
            'actors' => Actor::query()
                ->active()
                ->orderBy('name')
                ->limit(100)
                ->get()
                ->map(fn (Actor $actor): array => $this->actorSummary($actor))
                ->all(),
        ]);
    }

    public function channel(YoutubeChannel $channel): Response
    {
        $channel->loadCount('videos')->load([
            'videos' => fn ($query) => $query->with('channel')->orderByDesc('published_at'),
        ]);

        return Inertia::render('catalog/channel', [
            'channel' => $this->channelDetail($channel),
        ]);
    }

    public function video(Video $video): Response
    {
        $video->load([
            'channel',
            'actors' => fn ($query) => $query->active()->orderBy('name'),
        ]);

        return Inertia::render('catalog/video', [
            'video' => $this->videoDetail($video),
        ]);
    }

    public function actor(Actor $actor): Response
    {
        abort_if($actor->isMerged(), 404);

        $actor->load([
            'referenceUrls',
            'videos' => fn ($query) => $query->with('channel')->orderByDesc('published_at'),
        ]);

        return Inertia::render('catalog/actor', [
            'actor' => $this->actorDetail($actor),
        ]);
    }
}
