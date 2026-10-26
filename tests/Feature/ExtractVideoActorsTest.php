<?php

namespace Tests\Feature;

use App\Filament\Resources\YoutubeChannels\Pages\ViewYoutubeChannel;
use App\Filament\Resources\YoutubeChannels\RelationManagers\VideosRelationManager;
use App\Jobs\ExtractVideoActors;
use App\Models\Actor;
use App\Models\ActorReferenceUrl;
use App\Models\User;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ExtractVideoActorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        config([
            'services.gemini.key' => 'test-key-0123456789',
            'services.gemini.model' => 'gemini-3.6-flash',
        ]);
    }

    public function test_it_creates_and_links_actors_from_a_successful_extraction(): void
    {
        $video = $this->video();

        Http::fake(['*' => Http::response($this->fixture('extraction-success.json'), 200)]);

        (new ExtractVideoActors($video->id))->handle();

        $video->refresh();

        $this->assertSame(ExtractVideoActors::STATUS_COMPLETED, $video->extraction_status);
        $this->assertNull($video->extraction_error);
        $this->assertNotNull($video->extracted_at);
        $this->assertSame(3, Actor::count());
        $this->assertSame(3, $this->actorCount($video));
        $this->assertSame(3, ActorReferenceUrl::count());
        $this->assertSame(
            ['고은빈', '라현서', '홍연우'],
            Actor::orderBy('name')->pluck('name')->all(),
        );
        $this->assertSame(
            'https://www.instagram.com/rara__0228/',
            Actor::where('name', '라현서')->firstOrFail()->referenceUrls()->value('url'),
        );
        $this->assertNull(Actor::where('name', '라현서')->firstOrFail()->profile);
    }

    public function test_repeated_extraction_does_not_duplicate_actors_or_links(): void
    {
        $video = $this->video();

        Http::fake(['*' => Http::response($this->fixture('extraction-success.json'), 200)]);

        (new ExtractVideoActors($video->id))->handle();
        (new ExtractVideoActors($video->id))->handle();

        $this->assertSame(3, Actor::count());
        $this->assertSame(3, $this->actorCount($video));
        $this->assertSame(3, ActorReferenceUrl::count());
    }

    public function test_the_same_actor_name_on_a_different_video_is_not_automatically_merged(): void
    {
        $firstVideo = $this->video('video-1');
        $secondVideo = $this->video('video-2');

        Http::fake(['*' => Http::response($this->fixture('extraction-success.json'), 200)]);

        (new ExtractVideoActors($firstVideo->id))->handle();
        (new ExtractVideoActors($secondVideo->id))->handle();

        $this->assertSame(6, Actor::count());
        $this->assertSame(3, $this->actorCount($firstVideo));
        $this->assertSame(3, $this->actorCount($secondVideo));
    }

    public function test_a_failed_extraction_creates_no_actors_or_links_and_keeps_existing_ones(): void
    {
        $video = $this->video();
        $existing = Actor::create(['name' => 'Existing Actor']);
        $existing->videos()->attach($video->id);

        Http::fake(['*' => Http::response($this->fixture('api-key-invalid.json'), 400)]);

        (new ExtractVideoActors($video->id))->handle();

        $video->refresh();

        $this->assertSame(ExtractVideoActors::STATUS_FAILED, $video->extraction_status);
        $this->assertStringContainsString('API key not valid', (string) $video->extraction_error);
        $this->assertNull($video->extracted_at);
        $this->assertSame(1, Actor::count());
        $this->assertSame(1, $this->actorCount($video));
        $this->assertTrue($existing->videos()->whereKey($video->id)->exists());
    }

    public function test_the_video_action_extracts_actors_for_the_selected_video(): void
    {
        $channel = $this->channel();
        $selected = $this->video('selected-video', $channel);
        $untouched = $this->video('untouched-video', $channel);
        $existing = Actor::create(['name' => 'Existing Actor']);
        $existing->videos()->attach($untouched->id);

        Http::fake(['*' => Http::response($this->fixture('extraction-success.json'), 200)]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(VideosRelationManager::class, [
            'ownerRecord' => $channel,
            'pageClass' => ViewYoutubeChannel::class,
        ])
            ->callTableAction('extract', $selected)
            ->assertNotified('Gemini Actor Extraction completed');

        $this->assertSame(ExtractVideoActors::STATUS_COMPLETED, $selected->refresh()->extraction_status);
        $this->assertSame(3, $this->actorCount($selected));
        $this->assertSame(1, $this->actorCount($untouched));
        $this->assertSame(4, Actor::count());
    }

    public function test_the_video_action_shows_a_failure(): void
    {
        $channel = $this->channel();
        $video = $this->video('failing-video', $channel);

        Http::fake(['*' => Http::response($this->fixture('api-key-invalid.json'), 400)]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(VideosRelationManager::class, [
            'ownerRecord' => $channel,
            'pageClass' => ViewYoutubeChannel::class,
        ])
            ->callTableAction('extract', $video)
            ->assertNotified('Gemini Actor Extraction failed');

        $this->assertSame(ExtractVideoActors::STATUS_FAILED, $video->refresh()->extraction_status);
        $this->assertSame(0, Actor::count());
    }

    public function test_a_collected_video_is_not_extracted_until_an_explicit_run(): void
    {
        $video = $this->video();

        $this->assertNull($video->extraction_status);
        $this->assertSame(0, Actor::count());
        $this->assertSame(0, $this->actorCount($video));
    }

    private function channel(): YoutubeChannel
    {
        return YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create([
            'channel_id' => 'UC'.substr(md5(uniqid()), 0, 22),
            'title' => 'Test Channel',
            'uploads_playlist_id' => 'UU'.substr(md5(uniqid()), 0, 22),
        ]));
    }

    private function video(string $videoId = 'G6A-ONXm9D8', ?YoutubeChannel $channel = null): YoutubeVideo
    {
        $channel ??= $this->channel();

        return YoutubeVideo::create([
            'youtube_channel_id' => $channel->id,
            'video_id' => $videoId,
            'title' => '세상 쿨한 예쁜 누나와의 연애',
            'description' => '연상 테토녀 수빈 역 라현서 https://www.instagram.com/rara__0228/',
            'published_at' => Carbon::parse('2026-10-24T10:30:29Z'),
            'duration' => 'PT9M43S',
            'thumbnail_url' => 'https://example.com/'.$videoId.'.jpg',
            'view_count' => 1000,
        ]);
    }

    private function actorCount(YoutubeVideo $video): int
    {
        return Actor::whereHas('videos', fn ($query) => $query->where('youtube_videos.id', $video->id))->count();
    }

    private function fixture(string $name): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/gemini/'.$name);
    }
}
