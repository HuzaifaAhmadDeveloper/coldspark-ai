<?php

namespace Database\Factories;

use App\Models\Prospect;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prospect>
 */
class ProspectFactory extends Factory
{
    protected $model = Prospect::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'name'         => fake()->name(),
            'email'        => fake()->unique()->safeEmail(),
            'company'      => fake()->company(),
            'role'         => fake()->jobTitle(),
            'industry'     => fake()->word(),
            'pain_point'   => fake()->sentence(),
            'unsubscribed' => false,
        ];
    }
}
