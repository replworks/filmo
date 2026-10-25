<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateSource extends Model
{
    public const KIND_NAMUWIKI = 'namuwiki';

    public const KIND_NAVER = 'naver';

    protected $fillable = [
        'actor_candidate_id',
        'filmography_candidate_id',
        'kind',
        'url',
    ];

    /**
     * @return BelongsTo<ActorCandidate, $this>
     */
    public function actorCandidate(): BelongsTo
    {
        return $this->belongsTo(ActorCandidate::class);
    }

    /**
     * @return BelongsTo<FilmographyCandidate, $this>
     */
    public function filmographyCandidate(): BelongsTo
    {
        return $this->belongsTo(FilmographyCandidate::class);
    }
}
