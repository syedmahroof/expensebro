<?php

use App\Models\Group;
use App\Models\GroupExpense;
use App\Models\GroupExpenseSplit;
use App\Models\GroupMember;
use App\Models\User;

use function Pest\Laravel\actingAs;

// ─── Auth guards ──────────────────────────────────────────────────────────────

test('guests are redirected from groups index', function () {
    $this->get(route('groups.index'))->assertRedirect(route('login'));
});

// ─── Index ────────────────────────────────────────────────────────────────────

test('user can view their groups index', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user)->create(['name' => 'Trip 2025']);

    actingAs($user)
        ->get(route('groups.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Groups/Index')
            ->has('groups', 1)
            ->where('groups.0.name', 'Trip 2025')
        );
});

test('user cannot see another user\'s groups', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Group::factory()->for($other)->create();

    actingAs($user)
        ->get(route('groups.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('groups', 0));
});

// ─── Create group ─────────────────────────────────────────────────────────────

test('user can create a group with members', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('groups.store'), [
            'name' => 'Weekend Trip',
            'currency' => 'PKR',
            'members' => [
                ['name' => 'Ali', 'phone' => null],
                ['name' => 'Sara', 'phone' => '+92300000000'],
            ],
        ])
        ->assertRedirect();

    $group = $user->groups()->first();
    expect($group)->not->toBeNull()
        ->and($group->name)->toBe('Weekend Trip')
        ->and($group->members()->count())->toBe(2);
});

test('group name is required', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('groups.store'), [
            'name' => '',
            'members' => [['name' => 'Ali', 'phone' => null]],
        ])
        ->assertInvalid('name');
});

test('at least one member is required', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('groups.store'), [
            'name' => 'Test',
            'members' => [],
        ])
        ->assertInvalid('members');
});

// ─── Show group ───────────────────────────────────────────────────────────────

test('user can view their group', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user)->create();
    GroupMember::factory()->count(2)->for($group)->create();

    actingAs($user)
        ->get(route('groups.show', $group))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Groups/Show')
            ->where('group.id', $group->id)
            ->has('members', 2)
        );
});

test('user cannot view another user\'s group', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $group = Group::factory()->for($other)->create();

    actingAs($user)
        ->get(route('groups.show', $group))
        ->assertForbidden();
});

// ─── Delete group ─────────────────────────────────────────────────────────────

test('user can delete their group', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user)->create();

    actingAs($user)
        ->delete(route('groups.destroy', $group))
        ->assertRedirect(route('groups.index'));

    expect(Group::find($group->id))->toBeNull();
});

test('user cannot delete another user\'s group', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $group = Group::factory()->for($other)->create();

    actingAs($user)
        ->delete(route('groups.destroy', $group))
        ->assertForbidden();
});

// ─── Add expense ──────────────────────────────────────────────────────────────

test('user can add an expense with splits', function () {
    $user = User::factory()->create(['default_currency' => 'PKR']);
    $group = Group::factory()->for($user)->create(['currency' => 'PKR']);
    $members = GroupMember::factory()->count(2)->for($group)->create();

    actingAs($user)
        ->post(route('groups.expenses.store', $group), [
            'paid_by_member_id' => $members[0]->id,
            'amount' => 1000,
            'currency' => 'PKR',
            'description' => 'Dinner',
            'date' => '2026-05-01',
            'splits' => [
                ['group_member_id' => $members[0]->id, 'amount' => 500],
                ['group_member_id' => $members[1]->id, 'amount' => 500],
            ],
        ])
        ->assertRedirect();

    $expense = $group->expenses()->first();
    expect($expense)->not->toBeNull()
        ->and($expense->description)->toBe('Dinner')
        ->and($expense->splits()->count())->toBe(2);
});

test('expense description is required', function () {
    $user = User::factory()->create();
    $group = Group::factory()->for($user)->create();
    $member = GroupMember::factory()->for($group)->create();

    actingAs($user)
        ->post(route('groups.expenses.store', $group), [
            'paid_by_member_id' => $member->id,
            'amount' => 500,
            'description' => '',
            'date' => '2026-05-01',
            'splits' => [['group_member_id' => $member->id, 'amount' => 500]],
        ])
        ->assertInvalid('description');
});

// ─── Delete expense ───────────────────────────────────────────────────────────

test('user can delete an expense', function () {
    $user = User::factory()->create(['default_currency' => 'PKR']);
    $group = Group::factory()->for($user)->create();
    $member = GroupMember::factory()->for($group)->create();
    $expense = GroupExpense::factory()->for($group)->for($member, 'paidByMember')->create();

    actingAs($user)
        ->delete(route('groups.expenses.destroy', [$group, $expense]))
        ->assertRedirect();

    expect(GroupExpense::find($expense->id))->toBeNull();
});

// ─── Settle split ─────────────────────────────────────────────────────────────

test('user can mark a split as settled', function () {
    $user = User::factory()->create(['default_currency' => 'PKR']);
    $group = Group::factory()->for($user)->create();
    $member = GroupMember::factory()->for($group)->create();
    $expense = GroupExpense::factory()->for($group)->for($member, 'paidByMember')->create();
    $split = GroupExpenseSplit::factory()->create([
        'group_expense_id' => $expense->id,
        'group_member_id' => $member->id,
        'amount' => 500,
        'settled_at' => null,
    ]);

    actingAs($user)
        ->patch(route('groups.splits.settle', [$group, $split]))
        ->assertRedirect();

    expect($split->fresh()->settled_at)->not->toBeNull();
});

test('user can unsettle a split', function () {
    $user = User::factory()->create(['default_currency' => 'PKR']);
    $group = Group::factory()->for($user)->create();
    $member = GroupMember::factory()->for($group)->create();
    $expense = GroupExpense::factory()->for($group)->for($member, 'paidByMember')->create();
    $split = GroupExpenseSplit::factory()->create([
        'group_expense_id' => $expense->id,
        'group_member_id' => $member->id,
        'amount' => 500,
        'settled_at' => now(),
    ]);

    actingAs($user)
        ->patch(route('groups.splits.settle', [$group, $split]))
        ->assertRedirect();

    expect($split->fresh()->settled_at)->toBeNull();
});

// ─── Balance calculation ──────────────────────────────────────────────────────

test('group balances calculate correctly', function () {
    $user = User::factory()->create(['default_currency' => 'PKR']);
    $group = Group::factory()->for($user)->create(['currency' => 'PKR']);
    $alice = GroupMember::factory()->for($group)->create(['name' => 'Alice']);
    $bob = GroupMember::factory()->for($group)->create(['name' => 'Bob']);

    $expense = GroupExpense::factory()->for($group)->for($alice, 'paidByMember')->create([
        'amount' => 1000,
        'currency' => 'PKR',
        'converted_amount' => null,
    ]);

    GroupExpenseSplit::factory()->create([
        'group_expense_id' => $expense->id,
        'group_member_id' => $alice->id,
        'amount' => 500,
        'settled_at' => null,
    ]);
    GroupExpenseSplit::factory()->create([
        'group_expense_id' => $expense->id,
        'group_member_id' => $bob->id,
        'amount' => 500,
        'settled_at' => null,
    ]);

    $group->load(['members', 'expenses.splits.member', 'expenses.paidByMember']);
    $balances = $group->balances();

    expect($balances)->toHaveCount(1)
        ->and($balances[0]['fromName'])->toBe('Bob')
        ->and($balances[0]['toName'])->toBe('Alice')
        ->and($balances[0]['amount'])->toBe(500.0);
});
