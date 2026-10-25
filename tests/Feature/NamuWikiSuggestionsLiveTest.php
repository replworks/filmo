<?php

namespace Tests\Feature;

use App\Filament\Resources\ActorCandidates\Pages\ViewActorCandidate;
use App\Models\ActorCandidate;
use App\Models\CandidateSource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('live')]
class NamuWikiSuggestionsLiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        if (! env('RUN_LIVE_TESTS', false)) {
            $this->markTestSkipped('Set RUN_LIVE_TESTS=true to run live boundary tests.');
        }
    }

    public function test_it_gets_live_suggestions_and_shows_a_live_retrieval_failure(): void
    {
        $candidate = ActorCandidate::create([
            'activity_name' => 'Existing Activity',
            'name' => 'Existing Name',
            'related_link' => 'https://existing.example',
        ]);

        $this->actingAs(User::factory()->create());

        Livewire::test(ViewActorCandidate::class, ['record' => $candidate->getRouteKey()])
            ->set('namuWikiData.url', 'https://namu.wiki/w/송혜교')
            ->call('extractNamuWiki')
            ->assertSet('extractionError', null)
            ->assertSet('suggestions.name', '송혜교')
            ->assertSet('suggestions.source_url', 'https://namu.wiki/w/송혜교');

        $candidate->refresh();

        $this->assertSame('Existing Activity', $candidate->activity_name);
        $this->assertSame('Existing Name', $candidate->name);
        $this->assertSame('https://existing.example', $candidate->related_link);
        $this->assertDatabaseHas('candidate_sources', [
            'actor_candidate_id' => $candidate->id,
            'kind' => CandidateSource::KIND_NAMUWIKI,
            'url' => 'https://namu.wiki/w/송혜교',
        ]);
        $this->assertSame(1, CandidateSource::count());

        $missing = 'https://namu.wiki/w/FilmoProbeMissingPage-2026-10-26';

        Livewire::test(ViewActorCandidate::class, ['record' => $candidate->getRouteKey()])
            ->set('namuWikiData.url', $missing)
            ->call('extractNamuWiki')
            ->assertSet('namuWikiData.url', $missing)
            ->assertSet('suggestions', null)
            ->assertSee('NamuWiki extraction failed')
            ->assertSee('NamuWiki returned HTTP 404.');

        $this->assertSame('Existing Name', $candidate->refresh()->name);
        $this->assertSame(1, CandidateSource::count());
    }
}
