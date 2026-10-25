<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActorIdentity extends Model
{
    /**
     * @return HasMany<ActorCandidate, $this>
     */
    public function actorCandidates(): HasMany
    {
        return $this->hasMany(ActorCandidate::class);
    }
}
