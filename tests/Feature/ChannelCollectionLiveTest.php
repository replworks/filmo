<?php

namespace Tests\Feature;

use App\Jobs\CollectChannelVideos;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Cable8mm\Youtube\Youtube;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('live')]
class ChannelCollectionLiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();

        if (! env('RUN_LIVE_TESTS', false)) {
            $this->markTestSkipped('Set RUN_LIVE_TESTS=true to run live boundary tests.');
        }

        $key = (string) config('youtube.key');

        if ($key === '' || $key === 'YOUR_API_KEY') {
            $this->markTestSkipped('YOUTUBE_API_KEY is not configured.');
        }
    }

    public function test_it_collects_multiple_live_pages_and_records_a_live_failure(): void
    {
        $channel = YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create([
            'channel_id' => 'UCBR8-60-B28hp2BmDPdntcQ',
            'handle' => '@youtube',
            'title' => 'YouTube',
            'uploads_playlist_id' => 'UUBR8-60-B28hp2BmDPdntcQ',
        ]));

        $unread = YoutubeVideo::create([
            'youtube_channel_id' => $channel->id,
            'video_id' => 'unread-video',
            'title' => 'A Video outside the read pages',
            'published_at' => Carbon::parse('2000-01-01T00:00:00Z'),
        ]);

        (new CollectChannelVideos($channel->id, maxPages: 2))->handle();

        $channel->refresh();

        $this->assertSame('ok', $channel->sync_status);
        $this->assertNotNull($channel->last_synced_at);
        $this->assertNull($channel->last_error_reason);
        $this->assertGreaterThan(50, $channel->videos()->where('video_id', '!=', 'unread-video')->count());
        $this->assertNull($unread->refresh()->unavailable_at);

        $video = $channel->videos()->where('video_id', '!=', 'unread-video')->firstOrFail();

        $this->assertNotSame('', $video->title);
        $this->assertNotNull($video->description);
        $this->assertNotNull($video->published_at);
        $this->assertNotNull($video->duration);
        $this->assertNotNull($video->thumbnail_url);
        $this->assertNull($video->unavailable_at);
        $this->assertDatabaseCount('youtube_sync_errors', 0);

        $failing = YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create([
            'channel_id' => 'UCaaaaaaaaaaaaaaaaaaaaaa',
            'title' => 'Failing Channel',
            'uploads_playlist_id' => 'UUaaaaaaaaaaaaaaaaaaaaaa',
        ]));

        $this->app->instance(Youtube::class, new Youtube('invalid-key-for-filmo-live-probe'));

        (new CollectChannelVideos($failing->id, maxPages: 1))->handle();

        $failing->refresh();

        $this->assertSame('failed', $failing->sync_status);
        $this->assertSame('invalid_key', $failing->last_error_reason);
        $this->assertDatabaseHas('youtube_sync_errors', [
            'youtube_channel_id' => $failing->id,
            'reason' => 'invalid_key',
        ]);
    }
}
