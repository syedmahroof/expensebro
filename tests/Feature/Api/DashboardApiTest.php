<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Laravel\Sanctum\Sanctum;

test('returns dashboard stats for the current month', function () {
    $user = User::factory()->create(['default_currency' => 'PKR']);
    $wallet = Wallet::factory()->for($user)->create(['balance' => 5000]);

    Transaction::factory()->forUser($user, $wallet)->create([
        'type' => 'expense', 'amount' => 300, 'converted_amount' => 300,
        'currency' => 'PKR', 'date' => now()->toDateString(),
    ]);
    Transaction::factory()->forUser($user, $wallet)->create([
        'type' => 'income', 'amount' => 1000, 'converted_amount' => 1000,
        'currency' => 'PKR', 'date' => now()->toDateString(),
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('currency', 'PKR')
        ->assertJsonPath('stats.total_expenses', 300)
        ->assertJsonPath('stats.total_income', 1000)
        ->assertJsonPath('stats.balance', 700)
        ->assertJsonCount(1, 'wallets')
        ->assertJsonCount(2, 'recent_transactions')
        ->assertJsonStructure([
            'stats' => ['total_expenses', 'total_income', 'net_worth', 'savings_rate', 'tx_count'],
            'wallets' => [['id', 'name', 'balance']],
            'recent_transactions' => [['id', 'type', 'amount']],
            'category_breakdown',
        ]);
});

test('dashboard only counts the authenticated user\'s data', function () {
    $user = User::factory()->create(['default_currency' => 'PKR']);
    $wallet = Wallet::factory()->for($user)->create();
    Transaction::factory()->forUser($user, $wallet)->create([
        'type' => 'expense', 'amount' => 100, 'converted_amount' => 100,
        'date' => now()->toDateString(),
    ]);
    Transaction::factory()->create([
        'type' => 'expense', 'amount' => 9999, 'converted_amount' => 9999,
        'date' => now()->toDateString(),
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('stats.total_expenses', 100);
});

test('dashboard requires authentication', function () {
    $this->getJson('/api/v1/dashboard')->assertUnauthorized();
});

test('categories endpoint returns global and own categories', function () {
    $user = User::factory()->create();
    Category::factory()->create(['user_id' => null, 'name' => 'Global Cat']);
    Category::factory()->for($user)->create(['name' => 'My Cat']);
    Category::factory()->create(['name' => 'Other User Cat']);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/categories')->assertOk();
    $names = collect($response->json('data'))->pluck('name');

    expect($names)->toContain('Global Cat')
        ->and($names)->toContain('My Cat')
        ->and($names)->not->toContain('Other User Cat');
});
