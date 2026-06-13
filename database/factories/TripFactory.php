<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-2 months', '+1 month');

        return [
            'user_id' => User::factory(),
            'name' => $this->faker->randomElement(['Dubai Getaway', 'Northern Trip', 'Umrah', 'Business Trip', 'Family Vacation']),
            'destination' => $this->faker->city(),
            'budget' => $this->faker->randomFloat(2, 20000, 500000),
            'currency' => 'PKR',
            'start_date' => $start->format('Y-m-d'),
            'end_date' => (clone $start)->modify('+7 days')->format('Y-m-d'),
            'status' => 'active',
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }
}
