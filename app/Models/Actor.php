<?php

namespace App\Models;

use Cable8mm\Youtube\Models\YoutubeVideo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Actor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'activity_name',
        'url',
        'profile',
        'merged_into_actor_id',
    ];

    protected function casts(): array
    {
        return [
            'profile' => 'array',
        ];
    }

    /**
     * @return BelongsToMany<YoutubeVideo, $this>
     */
    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(YoutubeVideo::class, 'actor_video', 'actor_id', 'youtube_video_id');
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_actor_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function mergedActors(): HasMany
    {
        return $this->hasMany(self::class, 'merged_into_actor_id');
    }

    public function isMerged(): bool
    {
        return $this->merged_into_actor_id !== null;
    }

    public function survivor(): self
    {
        $actor = $this;

        while ($actor->merged_into_actor_id !== null) {
            $next = self::find($actor->merged_into_actor_id);

            if ($next === null) {
                break;
            }

            $actor = $next;
        }

        return $actor;
    }

    /**
     * @param  Builder<Actor>  $query
     * @return Builder<Actor>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('merged_into_actor_id');
    }
}
