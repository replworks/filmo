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
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('live')]
class NaverPersonInformationLiveTest extends TestCase
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

    public function test_the_supplied_naver_url_opens_and_the_saved_values_are_retained(): void
    {
        $candidate = ActorCandidate::create(['name' => 'Old Name']);

        $url = 'https://search.naver.com/search.naver?query=%EC%86%A1%ED%98%9C%EA%B5%90';

        $this->actingAs(User::factory()->create());

        Livewire::test(ViewActorCandidate::class, ['record' => $candidate->getRouteKey()])
            ->set('naverData.activity_name', 'Manual Activity')
            ->set('naverData.name', 'Manual Name')
            ->set('naverData.related_link', 'https://www.instagram.com/kyo1122')
            ->set('naverData.url', $url)
            ->assertSee('Open Naver Source')
            ->call('saveNaver')
            ->assertHasNoErrors()
            ->assertNotified('Actor Candidate saved');

        $candidate->refresh();

        $this->assertSame('Manual Activity', $candidate->activity_name);
        $this->assertSame('Manual Name', $candidate->name);
        $this->assertSame('https://www.instagram.com/kyo1122', $candidate->related_link);
        $this->assertDatabaseHas('candidate_sources', [
            'actor_candidate_id' => $candidate->id,
            'kind' => CandidateSource::KIND_NAVER,
            'url' => $url,
        ]);

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        ])
            ->timeout(30)
            ->get($url);

        $this->assertTrue($response->successful());
        $this->assertStringContainsString('송혜교', $response->body());
    }
}
