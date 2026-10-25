<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActorCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
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
}
