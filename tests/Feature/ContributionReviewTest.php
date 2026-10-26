<?php

namespace Tests\Feature;

use App\Filament\Pages\ContributionReview;
use App\Jobs\CollectChannelVideos;
use App\Jobs\ExtractVideoActors;
use App\Models\Actor;
use App\Models\Contribution;
use App\Models\User;
use App\Models\Video;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class ContributionReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_an_administrator_can_approve_an_actor_contribution(): void
    {
        $admin = User::factory()->admin()->create();
        $contributor = User::factory()->create();
        $actor = Actor::factory()->create([
            'name' => '초안 배우',
            'is_public' => false,
        ]);
        $contribution = $this->contribution($contributor, $actor, [
            'name' => '승인된 배우',
            'activity_name' => '활동명',
            'reference_urls' => ['https://namu.wiki/w/actor'],
        ]);

        $this->actingAs($admin);

        Livewire::test(ContributionReview::class)
            ->call('approve', $contribution->id, '승인합니다')
            ->assertNotified('Contribution approved');

        $actor->refresh();
        $contribution->refresh();

        $this->assertSame('승인된 배우', $actor->name);
        $this->assertTrue($actor->is_public);
        $this->assertSame(Contribution::STATUS_APPROVED, $contribution->status);
        $this->assertSame($admin->id, $contribution->reviewed_by);
        $this->assertSame('승인합니다', $contribution->review_note);
        $this->assertDatabaseHas('actor_reference_urls', [
            'actor_id' => $actor->id,
            'url' => 'https://namu.wiki/w/actor',
        ]);
    }

    public function test_rejecting_a_contribution_leaves_canonical_data_unchanged(): void
    {
        $admin = User::factory()->admin()->create();
        $contributor = User::factory()->create();
        $actor = Actor::factory()->create(['name' => '원래 배우']);
        $contribution = $this->contribution($contributor, $actor, [
            'name' => '거절될 이름',
            'reference_urls' => [],
        ]);

        $this->actingAs($admin);

        Livewire::test(ContributionReview::class)
            ->call('reject', $contribution->id, '근거 부족')
            ->assertNotified('Contribution rejected');

        $this->assertSame('원래 배우', $actor->refresh()->name);
        $this->assertSame(Contribution::STATUS_REJECTED, $contribution->refresh()->status);
        $this->assertSame('근거 부족', $contribution->review_note);
    }

    public function test_approving_a_channel_starts_initial_collection_and_video_does_not_start_extraction(): void
    {
        $admin = User::factory()->admin()->create();
        $contributor = User::factory()->create();
        $channel = YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create([
            'channel_id' => 'UC'.substr(md5('review-channel'), 0, 22),
            'title' => '초안 채널',
            'uploads_playlist_id' => 'UU'.substr(md5('review-channel'), 0, 22),
        ]));
        $channel->forceFill(['user_id' => $admin->id, 'is_public' => false])->save();
        $channelContribution = $this->contribution($contributor, $channel, [
            'title' => '승인된 채널',
            'handle' => '@approved',
            'uploads_playlist_id' => $channel->uploads_playlist_id,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(ContributionReview::class)->call('approve', $channelContribution->id);
        $this->assertTrue((bool) $channel->refresh()->is_public);
        Bus::assertDispatched(CollectChannelVideos::class);

        $video = Video::create([
            'youtube_channel_id' => $channel->id,
            'video_id' => 'review-video',
            'title' => '초안 영상',
            'published_at' => now(),
            'user_id' => $admin->id,
            'is_public' => false,
        ]);
        $videoContribution = $this->contribution($contributor, $video, [
            'title' => '승인된 영상',
            'description' => '설명',
            'published_at' => now()->toDateTimeString(),
            'duration' => 'PT1M',
            'thumbnail_url' => null,
            'view_count' => null,
            'like_count' => null,
            'comment_count' => null,
            'unavailable_at' => null,
        ]);

        Livewire::test(ContributionReview::class)->call('approve', $videoContribution->id);
        $this->assertTrue($video->refresh()->is_public);
        Bus::assertNotDispatched(ExtractVideoActors::class);
    }

    public function test_a_non_admin_cannot_open_the_contribution_review_page(): void
    {
        $contributor = User::factory()->create();

        $this->actingAs($contributor)
            ->get('/admin/contribution-review')
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function contribution(User $user, object $record, array $payload): Contribution
    {
        return Contribution::create([
            'user_id' => $user->id,
            'contributable_type' => $record::class,
            'contributable_id' => $record->getKey(),
            'payload' => $payload,
            'status' => Contribution::STATUS_PENDING,
        ]);
    }
}
