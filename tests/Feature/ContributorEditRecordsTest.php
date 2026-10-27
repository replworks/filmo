<?php

namespace Tests\Feature;

use App\Models\Actor;
use App\Models\Contribution;
use App\Models\User;
use App\Models\Video;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContributorEditRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_contributor_can_submit_an_actor_with_only_a_name(): void
    {
        $contributor = User::factory()->create();

        $this->actingAs($contributor)
            ->post('/contributor/actors', ['name' => '이름만 배우'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $actor = Actor::where('name', '이름만 배우')->firstOrFail();
        $contribution = Contribution::where('contributable_type', Actor::class)
            ->where('contributable_id', $actor->id)
            ->firstOrFail();

        $this->assertFalse($actor->is_public);
        $this->assertSame($contributor->id, $contribution->user_id);
        $this->assertSame(Contribution::STATUS_PENDING, $contribution->status);
        $this->get('/catalog/actors/'.$actor->id)->assertNotFound();
    }

    public function test_an_actor_edit_with_videos_is_one_pending_contribution(): void
    {
        $contributor = User::factory()->create();
        $video = $this->video($this->channel());
        $actor = Actor::factory()->create(['name' => '수정 전 배우']);

        $this->actingAs($contributor)
            ->patch('/contributor/actors/'.$actor->id, [
                'name' => '수정된 배우',
                'activity_name' => '활동명',
                'birth_date' => '1990-01-01',
                'birth_place' => '서울',
                'education' => '예시 대학교',
                'debut' => '2010',
                'agency' => '예시 소속사',
                'nationality' => '대한민국',
                'profile' => ['instagram' => 'https://instagram.com/actor'],
                'reference_urls' => [
                    'https://namu.wiki/w/actor',
                    'https://search.naver.com/actor',
                ],
                'video_ids' => [$video->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('수정 전 배우', $actor->refresh()->name);
        $this->assertSame(1, Contribution::count());

        $contribution = Contribution::firstOrFail();

        $this->assertSame('수정된 배우', $contribution->payload['name']);
        $this->assertSame([$video->id], $contribution->payload['videos']);
        $this->assertSame(
            ['https://namu.wiki/w/actor', 'https://search.naver.com/actor'],
            $contribution->payload['reference_urls'],
        );
        $this->assertDatabaseMissing('actor_video', [
            'actor_id' => $actor->id,
            'youtube_video_id' => $video->id,
        ]);
    }

    public function test_a_second_pending_contribution_for_the_same_record_is_a_conflict(): void
    {
        $contributor = User::factory()->create();
        $actor = Actor::factory()->create(['name' => '기존 배우']);

        $this->actingAs($contributor)
            ->patch('/contributor/actors/'.$actor->id, [
                'name' => '첫 번째 기여',
                'reference_urls' => [],
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($contributor)
            ->patch('/contributor/actors/'.$actor->id, [
                'name' => '두 번째 기여',
                'reference_urls' => [],
            ])
            ->assertSessionHasErrors('conflict');

        $this->assertSame(1, Contribution::count());
        $this->assertSame('첫 번째 기여', Contribution::firstOrFail()->payload['name']);
    }

    public function test_a_contributor_can_delete_their_pending_contribution(): void
    {
        $contributor = User::factory()->create();
        $actor = Actor::factory()->create();

        $this->actingAs($contributor)
            ->patch('/contributor/actors/'.$actor->id, [
                'name' => '기여 이름',
                'reference_urls' => [],
            ]);

        $contribution = Contribution::firstOrFail();

        $this->actingAs($contributor)
            ->delete('/contributor/contributions/'.$contribution->id)
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('contributions', ['id' => $contribution->id]);
    }

    public function test_actor_edit_pages_render_for_a_contributor(): void
    {
        $contributor = User::factory()->create();
        $actor = Actor::factory()->create();
        $actor->referenceUrls()->create(['url' => 'https://namu.wiki/w/actor']);

        $this->actingAs($contributor)
            ->get('/contributor/actors/'.$actor->id.'/edit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('contributor/actor')
                ->where('actor.id', $actor->id)
                ->where('actor.reference_urls.0', 'https://namu.wiki/w/actor')
                ->has('videos'));
    }

    public function test_a_guest_cannot_modify_contributor_records(): void
    {
        $actor = Actor::factory()->create(['name' => '원래 배우']);

        $this->post('/contributor/actors', ['name' => '새 배우'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('actors', ['name' => '새 배우']);
        $this->assertSame('원래 배우', $actor->refresh()->name);
        $this->assertSame(0, Contribution::count());
    }

    private function channel(): YoutubeChannel
    {
        return YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create([
            'channel_id' => 'UC'.substr(md5(uniqid()), 0, 22),
            'title' => '기존 채널',
            'uploads_playlist_id' => 'UU'.substr(md5(uniqid()), 0, 22),
        ]));
    }

    private function video(YoutubeChannel $channel): Video
    {
        $video = YoutubeVideo::create([
            'youtube_channel_id' => $channel->id,
            'video_id' => 'video-'.substr(md5(uniqid()), 0, 8),
            'title' => '기존 영상',
            'description' => '기존 설명',
            'published_at' => Carbon::parse('2026-10-01T00:00:00Z'),
            'duration' => 'PT10M',
        ]);

        return Video::findOrFail($video->id);
    }
}
