<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActorReferenceUrl extends Model
{
    protected $fillable = [
        'actor_id',
        'url',
    ];

    /**
     * @return BelongsTo<Actor, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }
}
