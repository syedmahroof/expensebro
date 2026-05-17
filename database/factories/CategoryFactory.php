<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    private static array $expenseCategories = [
        ['name' => 'Groceries', 'color' => '#f59e0b', 'icon' => 'shopping-cart'],
        ['name' => 'Transport', 'color' => '#3b82f6', 'icon' => 'car'],
        ['name' => 'Dining Out', 'color' => '#ef4444', 'icon' => 'utensils'],
        ['name' => 'Entertainment', 'color' => '#8b5cf6', 'icon' => 'film'],
        ['name' => 'Health', 'color' => '#10b981', 'icon' => 'heart'],
        ['name' => 'Shopping', 'color' => '#ec4899', 'icon' => 'shopping-bag'],
    ];

    private static array $incomeCategories = [
        ['name' => 'Salary', 'color' => '#22c55e', 'icon' => 'briefcase'],
        ['name' => 'Freelance', 'color' => '#14b8a6', 'icon' => 'computer-desktop'],
        ['name' => 'Investment', 'color' => '#a855f7', 'icon' => 'chart-bar'],
    ];

    public function definition(): array
    {
        $type = $this->faker->randomElement(['expense', 'income', 'both']);
        $pool = $type === 'income' ? self::$incomeCategories : self::$expenseCategories;
        $option = $this->faker->randomElement($pool);

        return [
            'user_id' => User::factory(),
            'name' => $option['name'],
            'type' => $type,
            'color' => $option['color'],
            'icon' => $option['icon'],
            'is_default' => false,
        ];
    }

    public function expense(): static
    {
        return $this->state(function () {
            $option = $this->faker->randomElement(self::$expenseCategories);

            return ['type' => 'expense', 'name' => $option['name'], 'color' => $option['color'], 'icon' => $option['icon']];
        });
    }

    public function income(): static
    {
        return $this->state(function () {
            $option = $this->faker->randomElement(self::$incomeCategories);

            return ['type' => 'income', 'name' => $option['name'], 'color' => $option['color'], 'icon' => $option['icon']];
        });
    }
}
