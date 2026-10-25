<?php

namespace Tests\Feature;

use App\Filament\Resources\ActorCandidates\Pages\ViewActorCandidate;
use App\Models\ActorCandidate;
use App\Models\CandidateSource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class NaverPersonInformationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_the_operator_can_save_manual_values_with_a_naver_source(): void
    {
        $candidate = ActorCandidate::create(['name' => 'Old Name']);

        Http::fake();
        $this->actingAs(User::factory()->create());

        Livewire::test(ViewActorCandidate::class, ['record' => $candidate->getRouteKey()])
            ->set('naverData.activity_name', 'Manual Activity')
            ->set('naverData.name', 'Manual Name')
            ->set('naverData.related_link', 'https://example.com/manual')
            ->set('naverData.url', 'https://search.naver.com/search.naver?query=송혜교')
            ->assertSee('Open Naver Source')
            ->call('saveNaver')
            ->assertHasNoErrors()
            ->assertNotified('Actor Candidate saved')
            ->assertSet('naverData.url', 'https://search.naver.com/search.naver?query=송혜교');

        $candidate->refresh();

        $this->assertSame('Manual Activity', $candidate->activity_name);
        $this->assertSame('Manual Name', $candidate->name);
        $this->assertSame('https://example.com/manual', $candidate->related_link);
        $this->assertDatabaseHas('candidate_sources', [
            'actor_candidate_id' => $candidate->id,
            'kind' => CandidateSource::KIND_NAVER,
            'url' => 'https://search.naver.com/search.naver?query=송혜교',
        ]);

        Http::assertNothingSent();
    }

    public function test_all_candidate_fields_can_be_empty_when_the_manual_values_are_saved(): void
    {
        $candidate = ActorCandidate::create([
            'activity_name' => 'Old Activity',
            'name' => 'Old Name',
            'related_link' => 'https://old.example',
        ]);

        Http::fake();
        $this->actingAs(User::factory()->create());

        Livewire::test(ViewActorCandidate::class, ['record' => $candidate->getRouteKey()])
            ->set('naverData.activity_name', '')
            ->set('naverData.name', '')
            ->set('naverData.related_link', '')
            ->set('naverData.url', 'https://search.naver.com/search.naver?query=송혜교')
            ->call('saveNaver')
            ->assertHasNoErrors();

        $candidate->refresh();

        $this->assertNull($candidate->activity_name);
        $this->assertNull($candidate->name);
        $this->assertNull($candidate->related_link);
        $this->assertDatabaseHas('candidate_sources', [
            'actor_candidate_id' => $candidate->id,
            'kind' => CandidateSource::KIND_NAVER,
        ]);

        Http::assertNothingSent();
    }

    public function test_a_non_naver_url_is_rejected_without_saving(): void
    {
        $candidate = ActorCandidate::create(['name' => 'Protected Name']);

        Http::fake();
        $this->actingAs(User::factory()->create());

        Livewire::test(ViewActorCandidate::class, ['record' => $candidate->getRouteKey()])
            ->set('naverData.name', 'Changed Name')
            ->set('naverData.url', 'https://example.com/person')
            ->call('saveNaver')
            ->assertHasErrors('naverData.url');

        $this->assertSame('Protected Name', $candidate->refresh()->name);
        $this->assertSame(0, CandidateSource::count());
        Http::assertNothingSent();
    }

    public function test_a_naver_url_is_required(): void
    {
        $candidate = ActorCandidate::create(['name' => 'Protected Name']);
        $this->actingAs(User::factory()->create());

        Livewire::test(ViewActorCandidate::class, ['record' => $candidate->getRouteKey()])
            ->set('naverData.name', 'Changed Name')
            ->set('naverData.url', '')
            ->call('saveNaver')
            ->assertHasErrors('naverData.url');

        $this->assertSame('Protected Name', $candidate->refresh()->name);
        $this->assertSame(0, CandidateSource::count());
    }

    public function test_the_saved_values_and_url_are_shown_when_the_page_is_reopened(): void
    {
        $candidate = ActorCandidate::create([
            'activity_name' => 'Saved Activity',
            'name' => 'Saved Name',
            'related_link' => 'https://saved.example',
        ]);

        $candidate->candidateSources()->create([
            'kind' => CandidateSource::KIND_NAVER,
            'url' => 'https://search.naver.com/search.naver?query=송혜교',
        ]);

        $this->actingAs(User::factory()->create());

        Livewire::test(ViewActorCandidate::class, ['record' => $candidate->getRouteKey()])
            ->assertSet('naverData.activity_name', 'Saved Activity')
            ->assertSet('naverData.name', 'Saved Name')
            ->assertSet('naverData.related_link', 'https://saved.example')
            ->assertSet('naverData.url', 'https://search.naver.com/search.naver?query=송혜교')
            ->assertSee('Open Naver Source');
    }

    public function test_the_actor_candidate_page_requires_authentication(): void
    {
        $candidate = ActorCandidate::create(['name' => 'Protected Candidate']);

        $this->get('/admin/actor-candidates/'.$candidate->id)->assertRedirect('/admin/login');
    }
}
