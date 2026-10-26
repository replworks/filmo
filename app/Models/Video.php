<?php

namespace App\Models;

use Cable8mm\Youtube\Models\YoutubeVideo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Video extends YoutubeVideo
{
    protected $table = 'youtube_videos';

    protected $fillable = [
        'youtube_channel_id',
        'video_id',
        'title',
        'description',
        'published_at',
        'duration',
        'thumbnail_url',
        'view_count',
        'like_count',
        'comment_count',
        'unavailable_at',
        'user_id',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_public' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @param  Builder<Video>  $query
     * @return Builder<Video>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * @return BelongsToMany<Actor, $this>
     */
    public function actors(): BelongsToMany
    {
        return $this->belongsToMany(Actor::class, 'actor_video', 'youtube_video_id', 'actor_id');
    }
}
