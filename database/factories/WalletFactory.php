<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    private static array $walletOptions = [
        ['name' => 'Cash', 'type' => 'cash', 'color' => '#10b981', 'icon' => 'banknotes'],
        ['name' => 'Bank Account', 'type' => 'bank', 'color' => '#6366f1', 'icon' => 'building-library'],
        ['name' => 'Credit Card', 'type' => 'card', 'color' => '#f59e0b', 'icon' => 'credit-card'],
        ['name' => 'Savings', 'type' => 'bank', 'color' => '#3b82f6', 'icon' => 'banknotes'],
        ['name' => 'Crypto', 'type' => 'crypto', 'color' => '#8b5cf6', 'icon' => 'currency-dollar'],
    ];

    public function definition(): array
    {
        $option = $this->faker->randomElement(self::$walletOptions);

        return [
            'user_id' => User::factory(),
            'name' => $option['name'],
            'type' => $option['type'],
            'currency' => 'PKR',
            'balance' => $this->faker->randomFloat(2, 1000, 500000),
            'color' => $option['color'],
            'icon' => $option['icon'],
            'is_default' => false,
            'is_active' => true,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
