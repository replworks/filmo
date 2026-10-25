<?php

namespace Tests\Feature;

use App\Filament\Resources\ActorCandidates\Pages\ListActorCandidates;
use App\Filament\Resources\ActorCandidates\Pages\ViewActorCandidate;
use App\Models\ActorCandidate;
use App\Models\CandidateSource;
use App\Models\FilmographyCandidate;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class NamuWikiSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_the_operator_can_get_suggestions_without_overwriting_the_candidate(): void
    {
        $candidate = ActorCandidate::create([
            'activity_name' => 'Existing Activity',
            'name' => 'Existing Name',
            'related_link' => 'https://existing.example',
        ]);

        Http::fake(['*' => Http::response($this->fixture('profile-page.html'), 200)]);
        $this->actingAs(User::factory()->create());

        Livewire::test(ViewActorCandidate::class, ['record' => $candidate->getRouteKey()])
            ->set('namuWikiData.url', 'https://namu.wiki/w/송혜교')
            ->call('extractNamuWiki')
            ->assertSet('extractionError', null)
            ->assertSet('suggestions.name', '송혜교')
            ->assertSet('suggestions.activity_name', null)
            ->assertSet('suggestions.related_link', 'https://www.instagram.com/kyo1122')
            ->assertSet('suggestions.source_url', 'https://namu.wiki/w/송혜교')
            ->assertSee('Suggested Actor Candidate Information')
            ->assertSee('These are suggestions only. The candidate values above are unchanged.');

        $candidate->refresh();

        $this->assertSame('Existing Activity', $candidate->activity_name);
        $this->assertSame('Existing Name', $candidate->name);
        $this->assertSame('https://existing.example', $candidate->related_link);

        $this->assertDatabaseHas('candidate_sources', [
            'actor_candidate_id' => $candidate->id,
            'kind' => CandidateSource::KIND_NAMUWIKI,
            'url' => 'https://namu.wiki/w/송혜교',
        ]);
        $this->assertSame(0, FilmographyCandidate::count());
        $this->assertSame(0, $candidate->filmographyCandidates()->count());
    }

    public function test_a_retrieval_failure_is_shown_and_keeps_the_url_and_candidate_values(): void
    {
        $candidate = ActorCandidate::create([
            'activity_name' => 'Existing Activity',
            'name' => 'Existing Name',
            'related_link' => 'https://existing.example',
        ]);

        Http::fake(['*' => Http::response($this->fixture('missing-page.html'), 404)]);
        $this->actingAs(User::factory()->create());

        $url = 'https://namu.wiki/w/FilmoProbeMissingPage-2026-10-26';

        Livewire::test(ViewActorCandidate::class, ['record' => $candidate->getRouteKey()])
            ->set('namuWikiData.url', $url)
            ->call('extractNamuWiki')
            ->assertSet('namuWikiData.url', $url)
            ->assertSet('suggestions', null)
            ->assertSee('NamuWiki extraction failed')
            ->assertSee('NamuWiki returned HTTP 404.');

        $this->assertNotNull($candidate->refresh()->name);
        $this->assertSame('Existing Name', $candidate->name);
        $this->assertSame(0, CandidateSource::count());
    }

    public function test_an_invalid_url_is_rejected_without_a_request(): void
    {
        $candidate = ActorCandidate::create(['name' => 'Existing Name']);

        Http::fake();
        $this->actingAs(User::factory()->create());

        Livewire::test(ViewActorCandidate::class, ['record' => $candidate->getRouteKey()])
            ->set('namuWikiData.url', 'https://example.com/w/송혜교')
            ->call('extractNamuWiki')
            ->assertSee('NamuWiki extraction failed')
            ->assertSee('Enter an https://namu.wiki/w/... URL.');

        Http::assertNothingSent();
        $this->assertSame(0, CandidateSource::count());
    }

    public function test_the_candidate_list_shows_actor_candidates(): void
    {
        $candidate = ActorCandidate::create([
            'activity_name' => 'Activity',
            'name' => 'Visible Candidate',
            'related_link' => 'https://example.com/actor',
        ]);

        $this->actingAs(User::factory()->create());

        Livewire::test(ListActorCandidates::class)
            ->assertCanSeeTableRecords([$candidate])
            ->assertTableColumnStateSet('name', 'Visible Candidate', $candidate)
            ->assertTableColumnStateSet('activity_name', 'Activity', $candidate);
    }

    public function test_the_actor_candidate_pages_require_authentication(): void
    {
        $candidate = ActorCandidate::create(['name' => 'Protected Candidate']);

        $this->get('/admin/actor-candidates')->assertRedirect('/admin/login');
        $this->get('/admin/actor-candidates/'.$candidate->id)->assertRedirect('/admin/login');
    }

    private function fixture(string $name): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/namuwiki/'.$name);
    }
}
