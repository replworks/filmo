<?php

namespace Tests\Feature;

use App\Jobs\CollectChannelVideos;
use App\Jobs\ExtractVideoActors;
use App\Models\ChannelRequest;
use App\Models\Contribution;
use App\Models\User;
use App\Models\Video;
use App\Services\ChannelRequest\ChannelRequestManager;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
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

    public function test_a_contributor_requests_a_live_channel_and_approval_starts_collection(): void
    {
        $contributor = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($contributor)
            ->from('/contributor')
            ->post('/contributor/channel-requests', ['channel' => '@YouTube'])
            ->assertRedirect('/contributor')
            ->assertSessionHas('status');

        $request = ChannelRequest::firstOrFail();

        $this->assertSame(ChannelRequest::STATUS_PENDING, $request->status);
        $this->assertDatabaseCount('youtube_channels', 0);

        $channel = app(ChannelRequestManager::class)->approve($request, $admin, '승인');

        $this->assertSame('YouTube', $channel->title);
        $this->assertSame('UUBR8-60-B28hp2BmDPdntcQ', $channel->uploads_playlist_id);
        $this->assertTrue((bool) $channel->is_public);
        $this->assertSame($admin->id, $channel->user_id);
        $this->assertSame(ChannelRequest::STATUS_APPROVED, $request->refresh()->status);
        Bus::assertDispatched(CollectChannelVideos::class);

        $failedRequest = ChannelRequest::create([
            'user_id' => $contributor->id,
            'input' => '@thisusernamedoesnotexist12345',
            'status' => ChannelRequest::STATUS_PENDING,
        ]);

        try {
            app(ChannelRequestManager::class)->approve($failedRequest, $admin);
            $this->fail('Expected the channel request approval to fail.');
        } catch (RuntimeException) {
            $this->assertSame(ChannelRequest::STATUS_PENDING, $failedRequest->refresh()->status);
        }

        $this->assertDatabaseCount('youtube_channels', 1);
    }

    public function test_a_contributor_adds_a_live_video_without_creating_a_channel_or_starting_extraction(): void
    {
        $contributor = User::factory()->create();

        $this->actingAs($contributor)
            ->from('/contributor')
            ->post('/contributor/videos', [
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ])
            ->assertRedirect('/contributor')
            ->assertSessionHas('status');

        $video = YoutubeVideo::where('video_id', 'dQw4w9WgXcQ')->firstOrFail();

        $this->assertNotSame('', $video->title);
        $this->assertNull($video->youtube_channel_id);
        $this->assertNotNull($video->channelid);
        $this->assertNotNull($video->channel_title);
        $this->assertFalse((bool) $video->is_public);
        $this->assertNull($video->extraction_status);
        $this->assertDatabaseCount('youtube_channels', 0);
        Bus::assertNotDispatched(ExtractVideoActors::class);
        $this->assertDatabaseHas('contributions', [
            'user_id' => $contributor->id,
            'contributable_type' => Video::class,
            'contributable_id' => $video->id,
            'status' => Contribution::STATUS_PENDING,
        ]);

        $this->actingAs($contributor)
            ->post('/contributor/videos', [
                'url' => 'https://www.youtube.com/watch?v=aaaaaaaaaaa',
            ])
            ->assertSessionHasErrors('url');

        $this->assertDatabaseCount('youtube_videos', 1);
    }
}
