<?php

namespace Tests\Feature;

use App\Jobs\CollectChannelVideos;
use App\Jobs\ExtractVideoActors;
use App\Models\User;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('live')]
class ContributorAddRecordsLiveTest extends TestCase
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

    public function test_a_contributor_adds_a_live_channel_and_initial_collection_starts(): void
    {
        $contributor = User::factory()->create();

        $this->actingAs($contributor)
            ->post('/contributor/channels', ['channel' => '@YouTube'])
            ->assertRedirect(route('contributor.index'))
            ->assertSessionHas('status');

        $channel = YoutubeChannel::where('channel_id', 'UCBR8-60-B28hp2BmDPdntcQ')->firstOrFail();

        $this->assertSame('YouTube', $channel->title);
        $this->assertSame('UUBR8-60-B28hp2BmDPdntcQ', $channel->uploads_playlist_id);
        Bus::assertDispatched(CollectChannelVideos::class, fn (CollectChannelVideos $job): bool => $job->channelId === $channel->id);

        $this->actingAs($contributor)
            ->post('/contributor/channels', ['channel' => '@thisusernamedoesnotexist12345'])
            ->assertSessionHasErrors('channel');

        $this->assertDatabaseCount('youtube_channels', 1);
    }

    public function test_a_contributor_adds_a_live_video_without_starting_extraction(): void
    {
        $contributor = User::factory()->create();

        $this->actingAs($contributor)
            ->post('/contributor/videos', [
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ])
            ->assertRedirect(route('contributor.index'))
            ->assertSessionHas('status');

        $video = YoutubeVideo::where('video_id', 'dQw4w9WgXcQ')->firstOrFail();

        $this->assertNotSame('', $video->title);
        $this->assertNotNull($video->youtube_channel_id);
        $this->assertNull($video->extraction_status);
        Bus::assertNotDispatched(ExtractVideoActors::class);

        $this->actingAs($contributor)
            ->post('/contributor/videos', [
                'url' => 'https://www.youtube.com/watch?v=aaaaaaaaaaa',
            ])
            ->assertSessionHasErrors('url');

        $this->assertDatabaseCount('youtube_videos', 1);
    }
}
