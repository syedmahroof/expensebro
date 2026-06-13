<?php

namespace Database\Factories;

use App\Models\Entity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entity>
 */
class EntityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->randomElement(['Honda City', 'Main Office', 'Sales Team', 'Home', 'Kids', 'Rental Flat']),
            'type' => $this->faker->randomElement(['vehicle', 'office', 'team', 'house', 'children', 'property', 'other']),
            'color' => $this->faker->hexColor(),
            'emoji' => $this->faker->optional()->randomElement(['🚗', '🏢', '👥', '🏠', '🧒', '🏘️']),
            'description' => $this->faker->optional(0.3)->sentence(),
        ];
    }
}
