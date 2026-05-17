<?php

namespace Database\Factories;

use App\Models\GroupExpense;
use App\Models\GroupMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupExpense>
 */
class GroupExpenseFactory extends Factory
{
    public function definition(): array
    {
        $member = GroupMember::factory()->create();

        return [
            'group_id' => $member->group_id,
            'paid_by_member_id' => $member->id,
            'amount' => fake()->randomFloat(2, 100, 10000),
            'currency' => 'PKR',
            'converted_amount' => null,
            'description' => fake()->words(3, true),
            'date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
