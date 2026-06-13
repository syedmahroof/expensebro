<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 500, 100000);

        return [
            'user_id' => User::factory(),
            'contact_name' => $this->faker->name(),
            'contact_phone' => $this->faker->optional()->phoneNumber(),
            'type' => $this->faker->randomElement(['lent', 'borrowed']),
            'amount' => $amount,
            'currency' => 'PKR',
            'converted_amount' => $amount,
            'description' => $this->faker->optional(0.5)->sentence(3),
            'due_date' => $this->faker->optional()->dateTimeBetween('now', '+2 months')?->format('Y-m-d'),
            'settled_at' => null,
            'notes' => null,
        ];
    }

    public function lent(): static
    {
        return $this->state(['type' => 'lent']);
    }

    public function borrowed(): static
    {
        return $this->state(['type' => 'borrowed']);
    }

    public function settled(): static
    {
        return $this->state(['settled_at' => now()]);
    }
}
