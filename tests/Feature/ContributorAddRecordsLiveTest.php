<?php

namespace Tests\Feature;

use App\Jobs\CollectChannelVideos;
use App\Jobs\ExtractVideoActors;
use App\Models\Actor;
use App\Models\ChannelRequest;
use App\Models\Contribution;
use App\Models\User;
use App\Services\ChannelRequest\ChannelRequestManager;
use App\Services\Contribution\ContributionManager;
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

    public function test_a_new_video_inside_an_actor_filmography_stays_channel_free(): void
    {
        $contributor = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($contributor)
            ->from('/contributor')
            ->post('/contributor/actors', [
                'name' => '필모 배우',
                'new_video_urls' => [
                    'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                ],
            ])
            ->assertRedirect('/contributor')
            ->assertSessionHas('status');

        $actor = Actor::where('name', '필모 배우')->firstOrFail();
        $video = YoutubeVideo::where('video_id', 'dQw4w9WgXcQ')->firstOrFail();
        $contribution = Contribution::where('contributable_type', Actor::class)
            ->where('contributable_id', $actor->id)
            ->firstOrFail();

        $this->assertFalse($actor->is_public);
        $this->assertNull($video->youtube_channel_id);
        $this->assertNotNull($video->channelid);
        $this->assertNotNull($video->channel_title);
        $this->assertFalse((bool) $video->is_public);
        $this->assertDatabaseCount('youtube_channels', 0);
        $this->assertContains($video->id, $contribution->payload['videos']);
        Bus::assertNotDispatched(ExtractVideoActors::class);

        app(ContributionManager::class)->approve($contribution, $admin, '승인');

        $this->assertTrue((bool) $actor->refresh()->is_public);
        $this->assertTrue((bool) $video->refresh()->is_public);
        $this->assertDatabaseHas('actor_video', [
            'actor_id' => $actor->id,
            'youtube_video_id' => $video->id,
        ]);
        Bus::assertNotDispatched(ExtractVideoActors::class);
    }
}
