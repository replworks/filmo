<?php

namespace Tests\Feature;

use App\Filament\Resources\ActorCandidates\Pages\ViewActorCandidate;
use App\Filament\Resources\FilmographyCandidates\Pages\EditFilmographyCandidate;
use App\Filament\Resources\FilmographyCandidates\Pages\ListFilmographyCandidates;
use App\Models\ActorCandidate;
use App\Models\CandidateSource;
use App\Models\FilmographyCandidate;
use App\Models\User;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class CandidateEditingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_the_operator_can_edit_and_save_filmography_candidate_fields_and_a_source(): void
    {
        $candidate = $this->candidate();

        $this->actingAs(User::factory()->create());

        Livewire::test(EditFilmographyCandidate::class, ['record' => $candidate->id])
            ->fillForm([
                'activity_name' => 'Edited Activity',
                'name' => 'Edited Name',
                'related_link' => 'https://edited.example',
                'video_title' => 'Edited Video',
                'channel_name' => 'Edited Channel',
                'published_date' => '2026-10-01 12:00:00',
                'youtube_video_url' => 'https://www.youtube.com/watch?v=edited',
                'timecode' => '00:01:20',
                'collection_method' => 'gemini',
                'confidence' => 0.75,
                'model_identifier' => 'gemini-3.6-flash',
                'prompt_identifier' => 'video-candidate-extraction/v1',
                'candidate_source_url' => 'https://source.example/evidence',
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertNotified();

        $candidate->refresh();

        $this->assertSame('Edited Activity', $candidate->activity_name);
        $this->assertSame('Edited Name', $candidate->name);
        $this->assertSame('https://edited.example', $candidate->related_link);
        $this->assertSame('Edited Video', $candidate->video_title);
        $this->assertSame('Edited Channel', $candidate->channel_name);
        $this->assertSame('https://www.youtube.com/watch?v=edited', $candidate->youtube_video_url);
        $this->assertSame('00:01:20', $candidate->timecode);
        $this->assertSame('gemini', $candidate->collection_method);
        $this->assertSame(0.75, $candidate->confidence);
        $this->assertSame('gemini-3.6-flash', $candidate->model_identifier);
        $this->assertSame('video-candidate-extraction/v1', $candidate->prompt_identifier);
        $this->assertSame('https://source.example/evidence', $candidate->candidate_source_url);
        $this->assertDatabaseHas('candidate_sources', [
            'filmography_candidate_id' => $candidate->id,
            'kind' => CandidateSource::KIND_MANUAL,
            'url' => 'https://source.example/evidence',
        ]);
    }

    public function test_all_optional_filmography_candidate_fields_can_be_empty(): void
    {
        $candidate = $this->candidate();

        $this->actingAs(User::factory()->create());

        Livewire::test(EditFilmographyCandidate::class, ['record' => $candidate->id])
            ->fillForm([
                'activity_name' => null,
                'name' => null,
                'related_link' => null,
                'video_title' => null,
                'channel_name' => null,
                'published_date' => null,
                'youtube_video_url' => null,
                'timecode' => null,
                'collection_method' => null,
                'confidence' => null,
                'model_identifier' => null,
                'prompt_identifier' => null,
                'candidate_source_url' => null,
            ])
            ->call('save')
            ->assertHasNoErrors();

        $candidate->refresh();

        $this->assertNull($candidate->activity_name);
        $this->assertNull($candidate->name);
        $this->assertNull($candidate->related_link);
        $this->assertNull($candidate->video_title);
        $this->assertNull($candidate->channel_name);
        $this->assertNull($candidate->published_date);
        $this->assertNull($candidate->youtube_video_url);
        $this->assertNull($candidate->timecode);
        $this->assertNull($candidate->collection_method);
        $this->assertNull($candidate->confidence);
        $this->assertNull($candidate->model_identifier);
        $this->assertNull($candidate->prompt_identifier);
        $this->assertNull($candidate->candidate_source_url);
        $this->assertNotNull($candidate->actor_candidate_id);
        $this->assertNotNull($candidate->youtube_video_id);
        $this->assertSame(FilmographyCandidate::REVIEW_STATUS_PENDING, $candidate->review_status);
        $this->assertSame(0, $candidate->candidateSources()->count());
    }

    public function test_a_failed_filmography_candidate_save_is_shown_and_keeps_the_entered_values(): void
    {
        $candidate = $this->candidate(['name' => 'Original Name']);

        FilmographyCandidate::saving(function (): void {
            throw new RuntimeException('The database rejected the save.');
        });

        $this->actingAs(User::factory()->create());

        Livewire::test(EditFilmographyCandidate::class, ['record' => $candidate->id])
            ->fillForm(['name' => 'Entered Name'])
            ->call('save')
            ->assertNotified('Candidate save failed')
            ->assertSet('data.name', 'Entered Name');

        $this->assertSame('Original Name', $candidate->refresh()->name);
    }

    public function test_a_failed_actor_candidate_save_is_shown_and_keeps_the_entered_values(): void
    {
        $candidate = ActorCandidate::create(['name' => 'Original Name']);

        ActorCandidate::saving(function (): void {
            throw new RuntimeException('The database rejected the save.');
        });

        $this->actingAs(User::factory()->create());

        Livewire::test(ViewActorCandidate::class, ['record' => $candidate->getRouteKey()])
            ->set('naverData.name', 'Entered Name')
            ->set('naverData.url', '')
            ->call('saveNaver')
            ->assertNotified('Actor Candidate save failed')
            ->assertSet('naverData.name', 'Entered Name');

        $this->assertSame('Original Name', $candidate->refresh()->name);
    }

    public function test_actor_candidate_fields_can_all_be_saved_empty(): void
    {
        $candidate = ActorCandidate::create([
            'activity_name' => 'Old Activity',
            'name' => 'Old Name',
            'related_link' => 'https://old.example',
        ]);

        $this->actingAs(User::factory()->create());

        Livewire::test(ViewActorCandidate::class, ['record' => $candidate->getRouteKey()])
            ->set('naverData.activity_name', '')
            ->set('naverData.name', '')
            ->set('naverData.related_link', '')
            ->set('naverData.url', '')
            ->call('saveNaver')
            ->assertHasNoErrors();

        $candidate->refresh();

        $this->assertNull($candidate->activity_name);
        $this->assertNull($candidate->name);
        $this->assertNull($candidate->related_link);
        $this->assertSame(0, $candidate->candidateSources()->count());
    }

    public function test_the_filmography_candidate_list_shows_candidates(): void
    {
        $candidate = $this->candidate(['name' => 'Listed Candidate']);

        $this->actingAs(User::factory()->create());

        Livewire::test(ListFilmographyCandidates::class)
            ->assertCanSeeTableRecords([$candidate])
            ->assertTableColumnStateSet('name', 'Listed Candidate', $candidate)
            ->assertTableColumnStateSet('review_status', FilmographyCandidate::REVIEW_STATUS_PENDING, $candidate);
    }

    public function test_the_filmography_candidate_pages_require_authentication(): void
    {
        $candidate = $this->candidate();

        $this->get('/admin/filmography-candidates')->assertRedirect('/admin/login');
        $this->get('/admin/filmography-candidates/'.$candidate->id.'/edit')->assertRedirect('/admin/login');
    }

    private function candidate(array $attributes = []): FilmographyCandidate
    {
        $channel = YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create([
            'channel_id' => 'UC'.substr(md5(uniqid()), 0, 22),
            'title' => 'Test Channel',
            'uploads_playlist_id' => 'UU'.substr(md5(uniqid()), 0, 22),
        ]));

        $video = YoutubeVideo::create([
            'youtube_channel_id' => $channel->id,
            'video_id' => 'video-'.substr(md5(uniqid()), 0, 8),
            'title' => 'Test Video',
            'description' => 'Description',
            'published_at' => Carbon::parse('2026-10-01T00:00:00Z'),
            'duration' => 'PT10M',
        ]);

        $actor = ActorCandidate::create([
            'activity_name' => 'Activity',
            'name' => 'Actor',
            'related_link' => 'https://example.com/actor',
        ]);

        return FilmographyCandidate::create(array_merge([
            'actor_candidate_id' => $actor->id,
            'youtube_video_id' => $video->id,
            'review_status' => FilmographyCandidate::REVIEW_STATUS_PENDING,
            'activity_name' => 'Activity',
            'name' => 'Candidate',
            'related_link' => 'https://example.com/candidate',
            'video_title' => 'Test Video',
            'channel_name' => 'Test Channel',
            'published_date' => Carbon::parse('2026-10-01T00:00:00Z'),
            'youtube_video_url' => 'https://www.youtube.com/watch?v=test',
            'collection_method' => 'gemini',
            'confidence' => 1.0,
            'model_identifier' => 'gemini-3.6-flash',
            'prompt_identifier' => 'video-candidate-extraction/v1',
        ], $attributes));
    }
}
