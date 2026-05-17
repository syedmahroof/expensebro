<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupMember>
 */
class GroupMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'name' => fake()->name(),
            'phone' => fake()->optional()->phoneNumber(),
        ];
    }
}
