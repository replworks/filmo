<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PresentsPublicCatalog;
use App\Models\Actor;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Inertia\Inertia;
use Inertia\Response;

class PublicLandingController extends Controller
{
    use PresentsPublicCatalog;

    public function __invoke(): Response
    {
        return Inertia::render('welcome', [
            'featuredActors' => Actor::query()
                ->active()
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (Actor $actor): array => $this->actorSummary($actor))
                ->all(),
            'featuredVideos' => YoutubeVideo::query()
                ->with('channel')
                ->latest('created_at')
                ->limit(6)
                ->get()
                ->map(fn (YoutubeVideo $video): array => $this->videoSummary($video))
                ->all(),
        ]);
    }
}
