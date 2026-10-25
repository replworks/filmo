<?php

namespace Tests\Feature;

use App\Models\Actor;
use App\Services\Actor\MergeManager;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use RuntimeException;
use Tests\TestCase;

class ActorMergeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    public function test_merging_actors_moves_their_videos_and_fills_missing_profile_values(): void
    {
        $survivor = Actor::create([
            'name' => 'Survivor',
            'birth_date' => '1981-11-22',
            'profile' => ['instagram' => 'https://instagram.com/survivor'],
        ]);
        $other = Actor::create([
            'name' => 'Other',
            'birth_place' => 'Seoul',
            'education' => 'Example University',
            'agency' => 'Example Agency',
            'nationality' => 'Korean',
            'profile' => ['youtube' => 'https://youtube.com/@other'],
        ]);

        $firstVideo = $this->video('video-1');
        $secondVideo = $this->video('video-2');

        $survivor->referenceUrls()->create(['url' => 'https://namu.wiki/w/survivor']);
        $other->referenceUrls()->create(['url' => 'https://namu.wiki/w/other']);

        $survivor->videos()->attach($firstVideo->id);
        $other->videos()->attach([$firstVideo->id, $secondVideo->id]);

        $merged = app(MergeManager::class)->merge([$survivor->id, $other->id], $survivor->id, confirmed: true);

        $this->assertSame($survivor->id, $merged->id);
        $this->assertSame(
            [$firstVideo->id, $secondVideo->id],
            $survivor->videos()->orderBy('youtube_videos.id')->pluck('youtube_videos.id')->all(),
        );
        $this->assertSame(0, $other->videos()->count());
        $this->assertSame($survivor->id, $other->refresh()->merged_into_actor_id);
        $this->assertSame('1981-11-22', $merged->birth_date->toDateString());
        $this->assertSame('Seoul', $merged->birth_place);
        $this->assertSame('Example University', $merged->education);
        $this->assertSame('Example Agency', $merged->agency);
        $this->assertSame('Korean', $merged->nationality);
        $this->assertSame('https://instagram.com/survivor', $merged->profile['instagram']);
        $this->assertSame('https://youtube.com/@other', $merged->profile['youtube']);
        $this->assertSame(
            ['https://namu.wiki/w/survivor', 'https://namu.wiki/w/other'],
            $merged->referenceUrls()->orderBy('id')->pluck('url')->all(),
        );
        $this->assertSame(0, $other->referenceUrls()->count());
    }

    public function test_merged_actors_are_hidden_from_the_active_scope(): void
    {
        $survivor = Actor::create(['name' => 'Survivor']);
        $other = Actor::create(['name' => 'Other']);

        app(MergeManager::class)->merge([$survivor->id, $other->id], $survivor->id, confirmed: true);

        $this->assertSame([$survivor->id], Actor::active()->pluck('id')->all());
    }

    public function test_matching_names_alone_do_not_merge_actors(): void
    {
        $first = Actor::create(['name' => 'Same Name']);
        $second = Actor::create(['name' => 'Same Name']);

        $this->assertNull($first->merged_into_actor_id);
        $this->assertNull($second->merged_into_actor_id);
        $this->assertSame(2, Actor::active()->count());
    }

    public function test_a_merge_requires_explicit_confirmation(): void
    {
        $first = Actor::create(['name' => 'First']);
        $second = Actor::create(['name' => 'Second']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Confirm the Actor merge before saving it.');

        app(MergeManager::class)->merge([$first->id, $second->id], $first->id);
    }

    public function test_a_merge_requires_two_actors_and_a_selected_survivor(): void
    {
        $actor = Actor::create(['name' => 'Single']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Select at least two Actors and one surviving Actor.');

        app(MergeManager::class)->merge([$actor->id], $actor->id, confirmed: true);
    }

    public function test_a_failed_merge_leaves_prior_associations_unchanged(): void
    {
        $survivor = Actor::create(['name' => 'Survivor']);
        $other = Actor::create(['name' => 'Other']);
        $video = $this->video('video-1');

        $other->videos()->attach($video->id);

        Actor::updating(function (Actor $actor): void {
            if ($actor->isDirty('merged_into_actor_id')) {
                throw new RuntimeException('The database rejected the merge.');
            }
        });

        $thrown = false;

        try {
            app(MergeManager::class)->merge([$survivor->id, $other->id], $survivor->id, confirmed: true);
        } catch (RuntimeException $e) {
            $thrown = true;
            $this->assertSame('The database rejected the merge.', $e->getMessage());
        }

        $this->assertTrue($thrown);
        $this->assertNull($other->refresh()->merged_into_actor_id);
        $this->assertSame(1, $other->videos()->count());
        $this->assertSame(0, $survivor->videos()->count());
    }

    public function test_merging_flattens_existing_merge_chains(): void
    {
        $first = Actor::create(['name' => 'First']);
        $second = Actor::create(['name' => 'Second']);
        $third = Actor::create(['name' => 'Third']);

        app(MergeManager::class)->merge([$first->id, $second->id], $second->id, confirmed: true);
        app(MergeManager::class)->merge([$second->id, $third->id], $third->id, confirmed: true);

        $this->assertSame($third->id, $first->refresh()->merged_into_actor_id);
        $this->assertSame($third->id, $second->refresh()->merged_into_actor_id);
    }

    private function video(string $videoId): YoutubeVideo
    {
        $channel = YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create([
            'channel_id' => 'UC'.substr(md5($videoId), 0, 22),
            'title' => 'Test Channel',
            'uploads_playlist_id' => 'UU'.substr(md5($videoId), 0, 22),
        ]));

        return YoutubeVideo::create([
            'youtube_channel_id' => $channel->id,
            'video_id' => $videoId,
            'title' => 'Video '.$videoId,
            'description' => 'Description',
            'published_at' => Carbon::parse('2026-10-01T00:00:00Z'),
            'duration' => 'PT10M',
        ]);
    }
}
