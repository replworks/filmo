<?php

namespace App\Models;

use Cable8mm\Youtube\Models\YoutubeVideo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Video extends YoutubeVideo
{
    protected $table = 'youtube_videos';

    /**
     * @return BelongsToMany<Actor, $this>
     */
    public function actors(): BelongsToMany
    {
        return $this->belongsToMany(Actor::class, 'actor_video', 'youtube_video_id', 'actor_id');
    }
}
