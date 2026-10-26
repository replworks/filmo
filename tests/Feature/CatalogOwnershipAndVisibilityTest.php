<?php

namespace Tests\Feature;

use App\Models\Actor;
use App\Models\Contribution;
use App\Models\User;
use App\Models\Video;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogOwnershipAndVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_hides_draft_records_and_associations(): void
    {
        $admin = User::factory()->admin()->create();
        $publicChannel = $this->channel('공개 채널', true, $admin);
        $draftChannel = $this->channel('초안 채널', false, $admin);
        $publicVideo = $this->video($publicChannel, true, $admin, 'public-video');
        $draftVideo = $this->video($publicChannel, false, $admin, 'draft-video');
        $publicActor = Actor::factory()->create(['name' => '공개 배우', 'user_id' => $admin->id, 'is_public' => true]);
        $draftActor = Actor::factory()->create(['name' => '초안 배우', 'user_id' => $admin->id, 'is_public' => false]);
        $publicActor->videos()->attach($publicVideo->id);
        $draftActor->videos()->attach($publicVideo->id);

        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->has('featuredActors', 1)
                ->where('featuredActors.0.name', '공개 배우')
                ->has('featuredVideos', 1)
                ->where('featuredVideos.0.title', '공개 영상'));

        $this->get('/catalog')
            ->assertInertia(fn (Assert $page) => $page
                ->has('channels', 1)
                ->where('channels.0.title', '공개 채널')
                ->has('videos', 1)
                ->where('videos.0.title', '공개 영상')
                ->has('actors', 1)
                ->where('actors.0.name', '공개 배우'));

        $this->get('/catalog/channels/'.$publicChannel->id)
            ->assertInertia(fn (Assert $page) => $page
                ->has('channel.videos', 1)
                ->where('channel.videos.0.title', '공개 영상'));

        $this->get('/catalog/channels/'.$draftChannel->id)->assertNotFound();
        $this->get('/catalog/videos/'.$draftVideo->id)->assertNotFound();
        $this->get('/catalog/actors/'.$draftActor->id)->assertNotFound();

        $this->get('/catalog/videos/'.$publicVideo->id)
            ->assertInertia(fn (Assert $page) => $page
                ->has('video.actors', 1)
                ->where('video.actors.0.name', '공개 배우'));
    }

    public function test_catalog_records_have_an_owner_and_public_flag(): void
    {
        $admin = User::factory()->admin()->create();
        $channel = $this->channel('소유 채널', true, $admin);
        $video = $this->video($channel, true, $admin, 'owned-video');
        $actor = Actor::factory()->create(['name' => '소유 배우', 'user_id' => $admin->id, 'is_public' => true]);

        $this->assertSame($admin->id, $channel->refresh()->user_id);
        $this->assertTrue((bool) $channel->is_public);
        $this->assertSame($admin->id, $video->refresh()->user_id);
        $this->assertTrue($video->is_public);
        $this->assertSame($admin->id, $actor->refresh()->user_id);
        $this->assertTrue($actor->is_public);
    }

    public function test_a_contribution_stores_a_json_payload_and_review_state(): void
    {
        $contributor = User::factory()->create();
        $reviewer = User::factory()->admin()->create();
        $actor = Actor::factory()->create();

        $contribution = Contribution::create([
            'user_id' => $contributor->id,
            'contributable_type' => Actor::class,
            'contributable_id' => $actor->id,
            'payload' => ['name' => '기여 이름', 'birth_date' => '1990-01-01'],
            'status' => Contribution::STATUS_PENDING,
        ]);

        $this->assertSame('기여 이름', $contribution->payload['name']);
        $this->assertSame(Contribution::STATUS_PENDING, $contribution->status);

        $contribution->update([
            'status' => Contribution::STATUS_APPROVED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => Carbon::now(),
            'review_note' => '승인',
        ]);

        $this->assertSame($reviewer->id, $contribution->refresh()->reviewer?->id);
        $this->assertNotNull($contribution->reviewed_at);
    }

    private function channel(string $title, bool $public, User $owner): YoutubeChannel
    {
        $channel = YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create([
            'channel_id' => 'UC'.substr(md5($title), 0, 22),
            'title' => $title,
            'uploads_playlist_id' => 'UU'.substr(md5($title), 0, 22),
        ]));

        $channel->forceFill([
            'user_id' => $owner->id,
            'is_public' => $public,
        ])->save();

        return $channel->refresh();
    }

    private function video(YoutubeChannel $channel, bool $public, User $owner, string $videoId): Video
    {
        return Video::create([
            'youtube_channel_id' => $channel->id,
            'video_id' => $videoId,
            'title' => $public ? '공개 영상' : '초안 영상',
            'description' => 'Description',
            'published_at' => Carbon::parse('2026-10-01T00:00:00Z'),
            'duration' => 'PT10M',
            'user_id' => $owner->id,
            'is_public' => $public,
        ]);
    }
}
