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

test('newly registered and onboarded user can add a transaction', function () {
    // 1. Register a new user
    $this->post('/register', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect();

    $user = User::where('email', 'newuser@example.com')->firstOrFail();
    $wallet = $user->wallets()->firstOrFail();

    // 2. Set default currency on onboarding
    $this->actingAs($user)
        ->post('/onboarding/currency', [
            'default_currency' => 'USD',
        ])->assertRedirect();

    expect($user->refresh()->default_currency)->toBe('USD');
    expect($wallet->refresh()->currency)->toBe('USD');

    // 3. Add a transaction
    $response = $this->actingAs($user)
        ->post(route('transactions.store'), [
            'wallet_id' => $wallet->id,
            'type' => 'expense',
            'amount' => 150.00,
            'currency' => 'USD',
            'date' => now()->toDateString(),
            'description' => 'Coffee',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'amount' => 150.00,
        'currency' => 'USD',
        'description' => 'Coffee',
    ]);
});

test('transactions page has all supported currencies in shared props', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Transactions/Index')
            ->where('currencies', config('currencies.supported'))
        );
});
