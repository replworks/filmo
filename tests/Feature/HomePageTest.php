<?php

namespace Tests\Feature;

use App\Models\Actor;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_service_landing_lists_featured_actors_and_videos(): void
    {
        $actor = Actor::factory()->create(['name' => '김배우']);
        $channel = $this->channel('테스트 채널');
        $this->video($channel, 'landing-video', '랜딩 영상');

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('welcome')
                ->has('featuredActors', 1)
                ->where('featuredActors.0.name', $actor->name)
                ->has('featuredVideos', 1)
                ->where('featuredVideos.0.title', '랜딩 영상')
                ->where('auth.user', null));
    }

    public function test_the_service_landing_is_public_and_can_be_empty(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('welcome')
                ->has('featuredActors', 0)
                ->has('featuredVideos', 0));
    }

    private function channel(string $title): YoutubeChannel
    {
        return YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create([
            'channel_id' => 'UC'.substr(md5($title), 0, 22),
            'title' => $title,
            'uploads_playlist_id' => 'UU'.substr(md5($title), 0, 22),
        ]));
    }

    private function video(YoutubeChannel $channel, string $videoId, string $title): YoutubeVideo
    {
        return YoutubeVideo::create([
            'youtube_channel_id' => $channel->id,
            'video_id' => $videoId,
            'title' => $title,
            'description' => 'Description',
            'published_at' => Carbon::parse('2026-10-01T00:00:00Z'),
            'duration' => 'PT10M',
        ]);
    }
}
