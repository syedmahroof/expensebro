<?php

use App\Models\User;
use App\Models\Wallet;

use function Pest\Laravel\actingAs;

test('guests are redirected from transactions page', function () {
    $this->get(route('transactions.index'))->assertRedirect(route('login'));
});

test('authenticated user sees the transactions page with wallets and other props', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create([
        'is_active' => true,
    ]);

    actingAs($user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Transactions/Index')
            ->has('wallets')
            ->has('categories')
            ->has('filters')
        );
});
