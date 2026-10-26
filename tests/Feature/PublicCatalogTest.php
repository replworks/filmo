<?php

namespace Tests\Feature;

use App\Models\Actor;
use App\Models\Video;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeSyncError;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_catalog_lists_channels_videos_and_actors(): void
    {
        $channel = $this->channel('공개 채널');
        $video = $this->video($channel, 'public-video', '공개 영상');
        $actor = $this->actor('공개 배우');

        $this->get('/catalog')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('catalog/index')
                ->has('channels', 1)
                ->where('channels.0.title', $channel->title)
                ->has('videos', 1)
                ->where('videos.0.title', $video->title)
                ->has('actors', 1)
                ->where('actors.0.name', $actor->name));
    }

    public function test_public_channel_video_and_actor_details_are_available_without_authentication(): void
    {
        $channel = $this->channel('상세 채널');
        $video = $this->video($channel, 'detail-video', '상세 영상');
        $actor = $this->actor('상세 배우');
        $actor->referenceUrls()->create(['url' => 'https://namu.wiki/w/actor']);
        $actor->videos()->attach($video->id);

        $this->get('/catalog/channels/'.$channel->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('catalog/channel')
                ->where('channel.title', $channel->title)
                ->has('channel.videos', 1)
                ->where('channel.videos.0.title', $video->title));

        $this->get('/catalog/videos/'.$video->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('catalog/video')
                ->where('video.title', $video->title)
                ->has('video.actors', 1)
                ->where('video.actors.0.name', $actor->name));

        $this->get('/catalog/actors/'.$actor->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('catalog/actor')
                ->where('actor.name', $actor->name)
                ->where('actor.reference_urls.0', 'https://namu.wiki/w/actor')
                ->has('actor.videos', 1)
                ->where('actor.videos.0.title', $video->title));
    }

    public function test_merged_actors_are_hidden_from_the_public_catalog(): void
    {
        $channel = $this->channel('병합 채널');
        $video = $this->video($channel, 'merged-video', '병합 영상');
        $active = $this->actor('활성 배우');
        $merged = $this->actor('병합 배우');
        $merged->update(['merged_into_actor_id' => $active->id]);
        $merged->videos()->attach($video->id);
        $active->videos()->attach($video->id);

        $this->get('/catalog')
            ->assertInertia(fn (Assert $page) => $page
                ->has('actors', 1)
                ->where('actors.0.name', $active->name));

        $this->get('/catalog/videos/'.$video->id)
            ->assertInertia(fn (Assert $page) => $page
                ->has('video.actors', 1)
                ->where('video.actors.0.name', $active->name));

        $this->get('/catalog/actors/'.$merged->id)->assertNotFound();
    }

    public function test_sync_errors_and_extraction_data_are_not_exposed(): void
    {
        $channel = $this->channel('오류 채널');
        $video = $this->video($channel, 'error-video', '오류 영상');
        $video->forceFill([
            'extraction_status' => 'failed',
            'extraction_error' => 'Extraction failed',
            'extracted_at' => Carbon::parse('2026-10-01T00:00:00Z'),
        ])->save();

        YoutubeSyncError::create([
            'youtube_channel_id' => $channel->id,
            'reason' => 'quota_exceeded',
            'http_code' => 403,
            'message' => 'Quota exhausted.',
        ]);

        $this->get('/catalog')
            ->assertInertia(fn (Assert $page) => $page
                ->missing('syncErrors')
                ->missing('videos.0.extraction_status')
                ->missing('videos.0.extraction_error')
                ->missing('canManage'));

        $this->get('/catalog/videos/'.$video->id)
            ->assertInertia(fn (Assert $page) => $page
                ->missing('video.extraction_status')
                ->missing('video.extraction_error')
                ->missing('canManage'));
    }

    public function test_the_public_catalog_shows_empty_state_data(): void
    {
        $this->get('/catalog')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('catalog/index')
                ->has('channels', 0)
                ->has('videos', 0)
                ->has('actors', 0));
    }

    private function channel(string $title): YoutubeChannel
    {
        return YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create([
            'channel_id' => 'UC'.substr(md5($title), 0, 22),
            'title' => $title,
            'handle' => '@'.substr(md5($title), 0, 8),
            'uploads_playlist_id' => 'UU'.substr(md5($title), 0, 22),
        ]));
    }

    private function video(YoutubeChannel $channel, string $videoId, string $title): Video
    {
        $video = YoutubeVideo::create([
            'youtube_channel_id' => $channel->id,
            'video_id' => $videoId,
            'title' => $title,
            'description' => 'Description',
            'published_at' => Carbon::parse('2026-10-01T00:00:00Z'),
            'duration' => 'PT10M',
        ]);

        return Video::findOrFail($video->id);
    }

    private function actor(string $name): Actor
    {
        return Actor::factory()->create([
            'name' => $name,
            'activity_name' => null,
        ]);
    }
}
