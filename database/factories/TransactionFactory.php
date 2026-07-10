<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    private static array $expenseDescriptions = [
        'Grocery run', 'Fuel fill-up', 'Restaurant dinner', 'Coffee shop',
        'Online order', 'Electricity bill', 'Internet bill', 'Medicine',
        'Movie tickets', 'Gym membership', 'Bus fare', 'Taxi ride',
    ];

    private static array $incomeDescriptions = [
        'Monthly salary', 'Freelance payment', 'Side project', 'Dividend',
        'Rental income', 'Bonus', 'Consulting fee', 'Commission',
    ];

    public function definition(): array
    {
        $type = $this->faker->randomElement(['expense', 'income', 'transfer']);
        $descriptions = $type === 'income' ? self::$incomeDescriptions : self::$expenseDescriptions;

        return [
            'user_id' => User::factory(),
            'wallet_id' => Wallet::factory(),
            'category_id' => null,
            'merchant_id' => null,
            'receipt_id' => null,
            'type' => $type,
            'amount' => $this->faker->randomFloat(2, 100, 50000),
            'currency' => 'PKR',
            'description' => $this->faker->randomElement($descriptions),
            'notes' => $this->faker->optional(0.2)->sentence(),
            'date' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'ai_parsed' => false,
            'ai_raw' => null,
            'is_recurring' => $this->faker->boolean(10),
        ];
    }

    public function expense(): static
    {
        return $this->state([
            'type' => 'expense',
            'amount' => $this->faker->randomFloat(2, 100, 15000),
            'description' => $this->faker->randomElement(self::$expenseDescriptions),
        ]);
    }

    public function income(): static
    {
        return $this->state([
            'type' => 'income',
            'amount' => $this->faker->randomFloat(2, 5000, 150000),
            'description' => $this->faker->randomElement(self::$incomeDescriptions),
        ]);
    }

    public function forUser(User $user, Wallet $wallet): static
    {
        return $this->state([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
        ]);
    }
}
