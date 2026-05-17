<?php

namespace Database\Factories;

use App\Models\GroupExpense;
use App\Models\GroupExpenseSplit;
use App\Models\GroupMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupExpenseSplit>
 */
class GroupExpenseSplitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group_expense_id' => GroupExpense::factory(),
            'group_member_id' => GroupMember::factory(),
            'amount' => fake()->randomFloat(2, 50, 5000),
            'settled_at' => null,
        ];
    }
}
