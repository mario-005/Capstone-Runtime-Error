<?php

namespace Database\Factories;

use App\Models\Role;
use App\RoleCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->randomElement(RoleCode::cases()),
            'name' => fake()->jobTitle(),
        ];
    }
}
