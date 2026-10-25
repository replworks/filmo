<?php

namespace Tests\Feature;

use App\Filament\Resources\ActorCandidates\ActorCandidateResource;
use App\Filament\Resources\FilmographyCandidates\FilmographyCandidateResource;
use App\Filament\Resources\FilmographyCandidates\Pages\EditFilmographyCandidate;
use App\Filament\Resources\FilmographyCandidates\Pages\ListFilmographyCandidates;
use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use App\Models\ActorCandidate;
use App\Models\CandidateSource;
use App\Models\FilmographyCandidate;
use App\Models\User;
use App\Services\Filmography\ReviewManager;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class FilmographyReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_the_pending_list_shows_only_pending_candidates(): void
    {
        $pending = $this->candidate(['name' => 'Pending Candidate']);
        $approved = $this->candidate([
            'name' => 'Approved Candidate',
            'review_status' => FilmographyCandidate::REVIEW_STATUS_APPROVED,
        ]);
        $rejected = $this->candidate([
            'name' => 'Rejected Candidate',
            'review_status' => FilmographyCandidate::REVIEW_STATUS_REJECTED,
        ]);

        $this->actingAs(User::factory()->create());

        Livewire::test(ListFilmographyCandidates::class)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$approved, $rejected])
            ->assertCountTableRecords(1)
            ->assertActionExists('channels')
            ->assertActionExists('actors')
            ->assertActionHasUrl('channels', YoutubeChannelResource::getUrl())
            ->assertActionHasUrl('actors', ActorCandidateResource::getUrl());
    }

    public function test_the_pending_list_is_sorted_by_published_date_and_limited_to_fifty(): void
    {
        $newest = Carbon::parse('2026-10-25T00:00:00Z');
        $candidates = [];

        for ($index = 0; $index < 51; $index++) {
            $candidates[] = $this->candidate([
                'name' => 'Candidate '.$index,
                'published_date' => $newest->copy()->subMinutes($index),
            ]);
        }

        $this->actingAs(User::factory()->create());

        Livewire::test(ListFilmographyCandidates::class)
            ->assertCountTableRecords(51)
            ->assertCanSeeTableRecords([$candidates[0], $candidates[49]], inOrder: true)
            ->assertCanNotSeeTableRecords([$candidates[50]]);
    }

    public function test_an_operator_can_approve_a_pending_candidate_with_a_note(): void
    {
        $candidate = $this->candidate(['name' => 'Approve Me']);
        $reviewer = User::factory()->create(['name' => 'Reviewer Name']);

        $this->actingAs($reviewer);

        Livewire::test(EditFilmographyCandidate::class, ['record' => $candidate->id])
            ->assertActionVisible('approve')
            ->assertActionVisible('reject')
            ->callAction('approve', ['review_note' => 'Looks good.'])
            ->assertNotified('Filmography Candidate approved')
            ->assertRedirect(FilmographyCandidateResource::getUrl('index'));

        $candidate->refresh();

        $this->assertSame(FilmographyCandidate::REVIEW_STATUS_APPROVED, $candidate->review_status);
        $this->assertSame('Reviewer Name', $candidate->reviewer);
        $this->assertNotNull($candidate->reviewed_at);
        $this->assertSame('Looks good.', $candidate->review_note);
        $this->assertTrue($candidate->isEligibleForPublicDisplay());

        Livewire::test(ListFilmographyCandidates::class)
            ->assertCanNotSeeTableRecords([$candidate]);
    }

    public function test_an_operator_can_reject_a_pending_candidate_without_a_note(): void
    {
        $candidate = $this->candidate(['name' => 'Reject Me']);
        $reviewer = User::factory()->create(['name' => 'Rejecting Reviewer']);

        $this->actingAs($reviewer);

        Livewire::test(EditFilmographyCandidate::class, ['record' => $candidate->id])
            ->callAction('reject')
            ->assertNotified('Filmography Candidate rejected');

        $candidate->refresh();

        $this->assertSame(FilmographyCandidate::REVIEW_STATUS_REJECTED, $candidate->review_status);
        $this->assertSame('Rejecting Reviewer', $candidate->reviewer);
        $this->assertNotNull($candidate->reviewed_at);
        $this->assertNull($candidate->review_note);
        $this->assertFalse($candidate->isEligibleForPublicDisplay());
    }

    public function test_reviewed_candidates_cannot_be_reviewed_again(): void
    {
        $candidate = $this->candidate([
            'review_status' => FilmographyCandidate::REVIEW_STATUS_APPROVED,
        ]);

        $this->actingAs(User::factory()->create());

        Livewire::test(EditFilmographyCandidate::class, ['record' => $candidate->id])
            ->assertActionHidden('approve')
            ->assertActionHidden('reject');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only a Pending Filmography Candidate can be reviewed.');

        app(ReviewManager::class)->approve($candidate->id, 'Another Reviewer');
    }

    public function test_a_failed_review_is_shown_and_leaves_the_candidate_pending(): void
    {
        $candidate = $this->candidate(['name' => 'Failed Review']);

        FilmographyCandidate::updating(function (): void {
            throw new RuntimeException('The database rejected the review.');
        });

        $this->actingAs(User::factory()->create());

        Livewire::test(EditFilmographyCandidate::class, ['record' => $candidate->id])
            ->callAction('approve', ['review_note' => 'Attempted'])
            ->assertNotified('Review failed');

        $candidate->refresh();

        $this->assertSame(FilmographyCandidate::REVIEW_STATUS_PENDING, $candidate->review_status);
        $this->assertNull($candidate->reviewer);
        $this->assertNull($candidate->reviewed_at);
        $this->assertNull($candidate->review_note);
        $this->assertFalse($candidate->isEligibleForPublicDisplay());
    }

    public function test_only_approved_candidates_are_eligible_for_public_display(): void
    {
        $pending = $this->candidate(['review_status' => FilmographyCandidate::REVIEW_STATUS_PENDING]);
        $approved = $this->candidate(['review_status' => FilmographyCandidate::REVIEW_STATUS_APPROVED]);
        $rejected = $this->candidate(['review_status' => FilmographyCandidate::REVIEW_STATUS_REJECTED]);

        $this->assertFalse($pending->isEligibleForPublicDisplay());
        $this->assertTrue($approved->isEligibleForPublicDisplay());
        $this->assertFalse($rejected->isEligibleForPublicDisplay());
        $this->assertSame([$approved->id], FilmographyCandidate::eligibleForPublicDisplay()->pluck('id')->all());
    }

    public function test_the_review_screen_shows_candidate_sources(): void
    {
        $candidate = $this->candidate();
        $candidate->candidateSources()->create([
            'kind' => CandidateSource::KIND_MANUAL,
            'url' => 'https://source.example/evidence',
        ]);

        $this->actingAs(User::factory()->create());

        Livewire::test(EditFilmographyCandidate::class, ['record' => $candidate->id])
            ->assertSee('Candidate Sources')
            ->assertSee('https://source.example/evidence');
    }

    private function candidate(array $attributes = []): FilmographyCandidate
    {
        $channel = YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create([
            'channel_id' => 'UC'.substr(md5(uniqid()), 0, 22),
            'title' => 'Review Channel',
            'uploads_playlist_id' => 'UU'.substr(md5(uniqid()), 0, 22),
        ]));

        $video = YoutubeVideo::create([
            'youtube_channel_id' => $channel->id,
            'video_id' => 'review-video-'.substr(md5(uniqid()), 0, 8),
            'title' => 'Review Video',
            'description' => 'Description',
            'published_at' => Carbon::parse('2026-10-01T00:00:00Z'),
            'duration' => 'PT10M',
        ]);

        $actor = ActorCandidate::create(['name' => 'Review Actor']);

        return FilmographyCandidate::create(array_merge([
            'actor_candidate_id' => $actor->id,
            'youtube_video_id' => $video->id,
            'review_status' => FilmographyCandidate::REVIEW_STATUS_PENDING,
            'activity_name' => 'Activity',
            'name' => 'Candidate',
            'video_title' => 'Review Video',
            'channel_name' => 'Review Channel',
            'published_date' => Carbon::parse('2026-10-01T00:00:00Z'),
            'youtube_video_url' => 'https://www.youtube.com/watch?v=review',
            'collection_method' => 'gemini',
            'confidence' => 1.0,
            'model_identifier' => 'gemini-3.6-flash',
            'prompt_identifier' => 'video-candidate-extraction/v1',
        ], $attributes));
    }
}
