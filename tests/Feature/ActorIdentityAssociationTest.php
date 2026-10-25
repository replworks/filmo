<?php

namespace Tests\Feature;

use App\Filament\Resources\ActorCandidates\Pages\ListActorCandidates;
use App\Models\ActorCandidate;
use App\Models\ActorIdentity;
use App\Models\User;
use App\Services\ActorIdentity\AssociationManager;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class ActorIdentityAssociationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_the_operator_can_confirm_that_selected_candidates_are_the_same_actor(): void
    {
        $first = ActorCandidate::create(['activity_name' => 'Activity', 'name' => 'Same Name']);
        $second = ActorCandidate::create(['activity_name' => 'Other Activity', 'name' => 'Same Name']);

        $this->assertNull($first->refresh()->actor_identity_id);
        $this->assertNull($second->refresh()->actor_identity_id);

        $this->actingAs(User::factory()->create());

        Livewire::test(ListActorCandidates::class)
            ->assertTableBulkActionExists('confirmIdentity')
            ->callTableBulkAction('confirmIdentity', [$first, $second])
            ->assertNotified('Actor Identity confirmed');

        $identity = ActorIdentity::sole();

        $this->assertSame($identity->id, $first->refresh()->actor_identity_id);
        $this->assertSame($identity->id, $second->refresh()->actor_identity_id);
        $this->assertSame(1, ActorIdentity::count());
    }

    public function test_matching_names_alone_never_associate_candidates(): void
    {
        $first = ActorCandidate::create(['name' => 'Same Name']);
        $second = ActorCandidate::create(['name' => 'Same Name']);

        $this->assertNull($first->actor_identity_id);
        $this->assertNull($second->actor_identity_id);
        $this->assertSame(0, ActorIdentity::count());
    }

    public function test_a_single_selected_candidate_is_rejected(): void
    {
        $candidate = ActorCandidate::create(['name' => 'Single Candidate']);

        $this->actingAs(User::factory()->create());

        Livewire::test(ListActorCandidates::class)
            ->callTableBulkAction('confirmIdentity', [$candidate])
            ->assertNotified('Actor Identity association failed');

        $this->assertNull($candidate->refresh()->actor_identity_id);
        $this->assertSame(0, ActorIdentity::count());
    }

    public function test_a_failed_association_keeps_prior_associations_unchanged(): void
    {
        $prior = ActorIdentity::create();
        $first = ActorCandidate::create(['name' => 'First', 'actor_identity_id' => $prior->id]);
        $second = ActorCandidate::create(['name' => 'Second']);

        ActorIdentity::creating(function (): void {
            throw new RuntimeException('The database rejected the association.');
        });

        $this->actingAs(User::factory()->create());

        Livewire::test(ListActorCandidates::class)
            ->callTableBulkAction('confirmIdentity', [$first, $second])
            ->assertNotified('Actor Identity association failed');

        $this->assertSame($prior->id, $first->refresh()->actor_identity_id);
        $this->assertNull($second->refresh()->actor_identity_id);
        $this->assertSame(1, ActorIdentity::count());
    }

    public function test_the_association_manager_requires_explicit_confirmation(): void
    {
        $first = ActorCandidate::create(['name' => 'First']);
        $second = ActorCandidate::create(['name' => 'Second']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Confirm the Actor Identity association before saving it.');

        app(AssociationManager::class)->associate([$first->id, $second->id]);
    }

    public function test_the_association_manager_requires_at_least_two_candidates(): void
    {
        $candidate = ActorCandidate::create(['name' => 'Single']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Select at least two Actor Candidates.');

        app(AssociationManager::class)->associate([$candidate->id], confirmed: true);
    }
}
