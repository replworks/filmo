<?php

namespace Database\Factories;

use App\Models\Actor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Actor>
 */
class ActorFactory extends Factory
{
    protected $model = Actor::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'activity_name' => null,
            'url' => null,
            'profile' => null,
        ];
    }
}
