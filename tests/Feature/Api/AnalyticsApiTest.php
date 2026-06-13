<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Laravel\Sanctum\Sanctum;

test('returns analytics with trend, breakdown and stats', function () {
    $user = User::factory()->create(['default_currency' => 'PKR']);
    $wallet = Wallet::factory()->for($user)->create();
    Transaction::factory()->forUser($user, $wallet)->create([
        'type' => 'expense', 'amount' => 500, 'converted_amount' => 500,
        'date' => now()->toDateString(),
    ]);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/analytics')
        ->assertOk()
        ->assertJsonPath('currency', 'PKR')
        ->assertJsonPath('stats.this_month.PKR', 500)
        ->assertJsonCount(6, 'monthly_trend')
        ->assertJsonCount(1, 'wallet_breakdown')
        ->assertJsonStructure([
            'monthly_trend' => [['month', 'expenses', 'income']],
            'category_breakdown',
            'wallet_breakdown' => [['id', 'name', 'balance']],
            'top_merchants',
            'stats' => ['this_month', 'last_month', 'change', 'total_transactions'],
        ]);
});

test('analytics only includes the authenticated user', function () {
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

    $this->getJson('/api/v1/analytics')
        ->assertOk()
        ->assertJsonPath('stats.this_month.PKR', 100)
        ->assertJsonPath('stats.total_transactions', 1);
});

test('analytics requires authentication', function () {
    $this->getJson('/api/v1/analytics')->assertUnauthorized();
});
