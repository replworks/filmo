<?php

namespace Tests\Feature;

use App\Filament\Resources\YoutubeChannels\Pages\ViewYoutubeChannel;
use App\Filament\Resources\YoutubeChannels\RelationManagers\VideosRelationManager;
use App\Jobs\ExtractVideoCandidates;
use App\Models\ActorCandidate;
use App\Models\FilmographyCandidate;
use App\Models\User;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ExtractVideoCandidatesTest extends TestCase
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

    public function test_a_successful_extraction_creates_pending_candidates_tied_to_the_video(): void
    {
        $video = $this->video();
        Http::fake(['*' => Http::response($this->fixture('extraction-success.json'), 200)]);

        (new ExtractVideoCandidates($video->id))->handle();

        $video->refresh();

        $this->assertSame(ExtractVideoCandidates::STATUS_COMPLETED, $video->extraction_status);
        $this->assertNull($video->extraction_error);
        $this->assertNotNull($video->extracted_at);

        $this->assertSame(3, ActorCandidate::count());
        $this->assertSame(3, FilmographyCandidate::count());

        $actor = ActorCandidate::where('name', '라현서')->firstOrFail();
        $this->assertSame('수빈', $actor->activity_name);
        $this->assertSame('https://www.instagram.com/rara__0228/', $actor->related_link);

        $candidate = FilmographyCandidate::where('name', '라현서')->firstOrFail();

        $this->assertSame(FilmographyCandidate::REVIEW_STATUS_PENDING, $candidate->review_status);
        $this->assertSame($video->id, $candidate->youtube_video_id);
        $this->assertSame($actor->id, $candidate->actor_candidate_id);
        $this->assertSame('세상 쿨한 예쁜 누나와의 연애', $candidate->video_title);
        $this->assertSame('채널 352', $candidate->channel_name);
        $this->assertSame('2026-10-24T10:30:29+00:00', $candidate->published_date->toIso8601String());
        $this->assertSame('https://www.youtube.com/watch?v='.$video->video_id, $candidate->youtube_video_url);
        $this->assertNull($candidate->timecode);
        $this->assertSame(1.0, $candidate->confidence);
        $this->assertSame('gemini', $candidate->collection_method);
        $this->assertSame('gemini-3.6-flash', $candidate->model_identifier);
        $this->assertSame('video-candidate-extraction/v1', $candidate->prompt_identifier);
        $this->assertSame('https://www.youtube.com/watch?v='.$video->video_id, $candidate->candidate_source_url);
    }

    public function test_a_repeated_extraction_creates_a_distinct_candidate_set(): void
    {
        $video = $this->video();
        Http::fake(['*' => Http::response($this->fixture('extraction-success.json'), 200)]);

        (new ExtractVideoCandidates($video->id))->handle();

        $firstActorIds = ActorCandidate::pluck('id')->all();
        $firstCandidateIds = FilmographyCandidate::pluck('id')->all();
        $firstValue = FilmographyCandidate::where('name', '라현서')->firstOrFail()->only([
            'activity_name', 'name', 'related_link', 'video_title', 'channel_name',
        ]);

        (new ExtractVideoCandidates($video->id))->handle();

        $this->assertSame(6, ActorCandidate::count());
        $this->assertSame(6, FilmographyCandidate::count());
        $this->assertSame($firstActorIds, ActorCandidate::whereIn('id', $firstActorIds)->pluck('id')->all());
        $this->assertSame($firstCandidateIds, FilmographyCandidate::whereIn('id', $firstCandidateIds)->pluck('id')->all());
        $this->assertSame($firstValue, FilmographyCandidate::whereIn('id', $firstCandidateIds)->where('name', '라현서')->firstOrFail()->only([
            'activity_name', 'name', 'related_link', 'video_title', 'channel_name',
        ]));
        $this->assertNotSame($firstActorIds, ActorCandidate::pluck('id')->all());
        $this->assertNotSame($firstCandidateIds, FilmographyCandidate::pluck('id')->all());
    }

    public function test_a_failed_extraction_creates_no_candidates_and_keeps_prior_candidates(): void
    {
        $video = $this->video();

        Http::fakeSequence()
            ->push($this->fixture('extraction-success.json'), 200)
            ->push($this->fixture('api-key-invalid.json'), 400);

        (new ExtractVideoCandidates($video->id))->handle();

        $actors = ActorCandidate::orderBy('id')->get()->toArray();
        $candidates = FilmographyCandidate::orderBy('id')->get()->toArray();

        (new ExtractVideoCandidates($video->id))->handle();

        $video->refresh();

        $this->assertSame(ExtractVideoCandidates::STATUS_FAILED, $video->extraction_status);
        $this->assertStringContainsString('API key not valid', (string) $video->extraction_error);
        $this->assertNull($video->extracted_at);
        $this->assertSame($actors, ActorCandidate::orderBy('id')->get()->toArray());
        $this->assertSame($candidates, FilmographyCandidate::orderBy('id')->get()->toArray());
    }

    public function test_a_collected_video_is_not_extracted_until_an_explicit_run(): void
    {
        $video = $this->video();

        $this->assertNull($video->extraction_status);
        $this->assertSame(0, ActorCandidate::count());
        $this->assertSame(0, FilmographyCandidate::count());
    }

    public function test_the_extraction_status_column_shows_an_in_progress_run(): void
    {
        $channel = YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create([
            'channel_id' => 'UCcccccccccccccccccccccc',
            'title' => 'In Progress Channel',
            'uploads_playlist_id' => 'UUuploads3',
        ]));

        $video = $this->video($channel, 'in-progress-video');
        $video->extraction_status = ExtractVideoCandidates::STATUS_IN_PROGRESS;
        $video->save();

        $this->actingAs(User::factory()->create());

        Livewire::test(VideosRelationManager::class, [
            'ownerRecord' => $channel,
            'pageClass' => ViewYoutubeChannel::class,
        ])->assertTableColumnStateSet('extraction_status', 'In progress', $video);
    }

    public function test_the_extraction_action_runs_for_the_selected_video_and_shows_its_state(): void
    {
        $channel = YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create([
            'channel_id' => 'UCaaaaaaaaaaaaaaaaaaaaaa',
            'title' => 'Test Channel',
            'uploads_playlist_id' => 'UUuploads',
        ]));

        $selected = $this->video($channel, 'selected-video');
        $untouched = $this->video($channel, 'untouched-video');

        Http::fake(['*' => Http::response($this->fixture('extraction-success.json'), 200)]);

        $this->actingAs(User::factory()->create());

        Livewire::test(VideosRelationManager::class, [
            'ownerRecord' => $channel,
            'pageClass' => ViewYoutubeChannel::class,
        ])
            ->assertTableColumnStateSet('extraction_status', 'Not started', $selected)
            ->callTableAction('extract', $selected)
            ->assertHasNoTableActionErrors()
            ->assertNotified('Gemini Candidate Extraction completed');

        $this->assertSame(ExtractVideoCandidates::STATUS_COMPLETED, $selected->refresh()->extraction_status);

        Livewire::test(VideosRelationManager::class, [
            'ownerRecord' => $channel,
            'pageClass' => ViewYoutubeChannel::class,
        ])->assertTableColumnStateSet('extraction_status', 'Completed', $selected);
        $this->assertNull($untouched->refresh()->extraction_status);
        $this->assertSame(3, FilmographyCandidate::where('youtube_video_id', $selected->id)->count());
        $this->assertSame(0, FilmographyCandidate::where('youtube_video_id', $untouched->id)->count());
    }

    public function test_the_extraction_action_shows_a_failure(): void
    {
        $channel = YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create([
            'channel_id' => 'UCbbbbbbbbbbbbbbbbbbbbbb',
            'title' => 'Failing Channel',
            'uploads_playlist_id' => 'UUuploads2',
        ]));

        $video = $this->video($channel, 'failing-video');

        Http::fakeSequence()->push($this->fixture('api-key-invalid.json'), 400);

        $this->actingAs(User::factory()->create());

        Livewire::test(VideosRelationManager::class, [
            'ownerRecord' => $channel,
            'pageClass' => ViewYoutubeChannel::class,
        ])
            ->callTableAction('extract', $video)
            ->assertNotified('Gemini Candidate Extraction failed');

        $this->assertSame(ExtractVideoCandidates::STATUS_FAILED, $video->refresh()->extraction_status);
        $this->assertSame(0, ActorCandidate::count());
        $this->assertSame(0, FilmographyCandidate::count());
    }

    private function video(?YoutubeChannel $channel = null, string $videoId = 'G6A-ONXm9D8'): YoutubeVideo
    {
        $channel ??= YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create([
            'channel_id' => 'UC'.substr(md5($videoId), 0, 22),
            'title' => '채널 352',
            'uploads_playlist_id' => 'UU'.substr(md5($videoId), 0, 22),
        ]));

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

    private function fixture(string $name): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/gemini/'.$name);
    }
}
