<?php

namespace App\Services\ActorIdentity;

use App\Models\ActorCandidate;
use App\Models\ActorIdentity;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AssociationManager
{
    /**
     * Associate the selected Actor Candidates with one new Actor Identity.
     *
     * @param  array<int, int|string>  $candidateIds
     *
     * @throws RuntimeException
     */
    public function associate(array $candidateIds, bool $confirmed = false): ActorIdentity
    {
        if (! $confirmed) {
            throw new RuntimeException('Confirm the Actor Identity association before saving it.');
        }

        $candidateIds = array_values(array_unique(array_map('intval', $candidateIds)));

        if (count($candidateIds) < 2) {
            throw new RuntimeException('Select at least two Actor Candidates.');
        }

        return DB::transaction(function () use ($candidateIds): ActorIdentity {
            $candidates = ActorCandidate::whereIn('id', $candidateIds)->get();

            if ($candidates->count() < 2) {
                throw new RuntimeException('Select at least two Actor Candidates.');
            }

            $identity = ActorIdentity::create();

            ActorCandidate::whereIn('id', $candidates->modelKeys())
                ->update(['actor_identity_id' => $identity->id]);

            return $identity;
        });
    }
}
