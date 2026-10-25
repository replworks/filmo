<?php

namespace App\Models;

use Cable8mm\Youtube\Models\YoutubeVideo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FilmographyCandidate extends Model
{
    public const REVIEW_STATUS_PENDING = 'pending';

    public const REVIEW_STATUS_APPROVED = 'approved';

    public const REVIEW_STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'actor_candidate_id',
        'youtube_video_id',
        'review_status',
        'activity_name',
        'name',
        'related_link',
        'video_title',
        'channel_name',
        'published_date',
        'youtube_video_url',
        'timecode',
        'collection_method',
        'confidence',
        'model_identifier',
        'prompt_identifier',
        'candidate_source_url',
        'reviewer',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'published_date' => 'datetime',
            'confidence' => 'float',
            'reviewed_at' => 'datetime',
        ];
    }

    public function isEligibleForPublicDisplay(): bool
    {
        return $this->review_status === self::REVIEW_STATUS_APPROVED;
    }

    /**
     * @param  Builder<FilmographyCandidate>  $query
     * @return Builder<FilmographyCandidate>
     */
    public function scopeEligibleForPublicDisplay(Builder $query): Builder
    {
        return $query->where('review_status', self::REVIEW_STATUS_APPROVED);
    }

    /**
     * @return BelongsTo<ActorCandidate, $this>
     */
    public function actorCandidate(): BelongsTo
    {
        return $this->belongsTo(ActorCandidate::class);
    }

    /**
     * @return BelongsTo<YoutubeVideo, $this>
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(YoutubeVideo::class, 'youtube_video_id');
    }

    /**
     * @return HasMany<CandidateSource, $this>
     */
    public function candidateSources(): HasMany
    {
        return $this->hasMany(CandidateSource::class);
    }
}
