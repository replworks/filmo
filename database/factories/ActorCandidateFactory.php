<?php

namespace Database\Factories;

use App\Models\ActorCandidate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActorCandidate>
 */
class ActorCandidateFactory extends Factory
{
    protected $model = ActorCandidate::class;

    public function definition(): array
    {
        return [
            'activity_name' => fake()->name(),
            'name' => fake()->name(),
            'related_link' => fake()->url(),
        ];
    }
}
