<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Merchant>
 */
class MerchantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->company(),
            'category_id' => null,
            'logo' => null,
        ];
    }

    public function withCategory(): static
    {
        return $this->state(['category_id' => Category::factory()]);
    }
}
