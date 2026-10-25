<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActorCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_identity_id',
        'activity_name',
        'name',
        'related_link',
    ];

    /**
     * @return HasMany<FilmographyCandidate, $this>
     */
    public function filmographyCandidates(): HasMany
    {
        return $this->hasMany(FilmographyCandidate::class);
    }

    /**
     * @return HasMany<CandidateSource, $this>
     */
    public function candidateSources(): HasMany
    {
        return $this->hasMany(CandidateSource::class);
    }

    /**
     * @return BelongsTo<ActorIdentity, $this>
     */
    public function actorIdentity(): BelongsTo
    {
        return $this->belongsTo(ActorIdentity::class);
    }
}
