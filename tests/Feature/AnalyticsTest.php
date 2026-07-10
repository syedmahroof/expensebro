<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

test('guests are redirected to the login page', function () {
    $this->get(route('analytics'))->assertRedirect(route('login'));
});

test('authenticated users can visit analytics', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('analytics'))->assertOk();
});

test('analytics exposes currency-keyed stats and trend', function () {
    $user = User::factory()->create(['default_currency' => 'PKR']);
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'PKR']);
    Transaction::factory()->forUser($user, $wallet)->create([
        'type' => 'expense', 'amount' => 500, 'currency' => 'PKR',
        'date' => now()->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('analytics'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Analytics')
            ->has('monthlyTrend', 6)
            ->where('stats.thisMonth.PKR', fn ($v) => (float) $v === 500.0)
            ->where('stats.totalTransactions', 1)
            ->has('walletBreakdown', 1)
        );
});

test('analytics keeps each currency separate in monthly totals', function () {
    $user = User::factory()->create(['default_currency' => 'PKR']);
    $pkr = Wallet::factory()->for($user)->create(['currency' => 'PKR']);
    $usd = Wallet::factory()->for($user)->create(['currency' => 'USD']);
    Transaction::factory()->forUser($user, $pkr)->create([
        'type' => 'expense', 'amount' => 300, 'currency' => 'PKR',
        'date' => now()->toDateString(),
    ]);
    Transaction::factory()->forUser($user, $usd)->create([
        'type' => 'expense', 'amount' => 40, 'currency' => 'USD',
        'date' => now()->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('analytics'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.thisMonth.PKR', fn ($v) => (float) $v === 300.0)
            ->where('stats.thisMonth.USD', fn ($v) => (float) $v === 40.0)
        );
});
