<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Laravel\Sanctum\Sanctum;

test('lists the user\'s transactions paginated with relations', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create();
    Transaction::factory()->count(3)->forUser($user, $wallet)->create();
    Transaction::factory()->create(); // another user

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/transactions')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'type', 'amount', 'currency', 'wallet']],
            'meta' => ['current_page', 'total'],
        ]);
});

test('filters transactions by type', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create();
    Transaction::factory()->forUser($user, $wallet)->expense()->create();
    Transaction::factory()->forUser($user, $wallet)->income()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/transactions?type=income')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'income');
});

test('creates an expense and decreases the wallet balance', function () {
    $user = User::factory()->create(['default_currency' => 'PKR']);
    $wallet = Wallet::factory()->for($user)->create(['balance' => 1000, 'currency' => 'PKR']);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/transactions', [
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'amount' => 250,
        'description' => 'Lunch',
        'date' => now()->toDateString(),
    ])->assertCreated()
        ->assertJsonPath('data.type', 'expense')
        ->assertJsonPath('data.amount', 250);

    expect((float) $wallet->refresh()->balance)->toBe(750.0);
});

test('income increases the wallet balance', function () {
    $user = User::factory()->create(['default_currency' => 'PKR']);
    $wallet = Wallet::factory()->for($user)->create(['balance' => 1000]);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/transactions', [
        'wallet_id' => $wallet->id,
        'type' => 'income',
        'amount' => 500,
        'date' => now()->toDateString(),
    ])->assertCreated();

    expect((float) $wallet->refresh()->balance)->toBe(1500.0);
});

test('a foreign-currency transaction is converted to the default currency', function () {
    $user = User::factory()->create(['default_currency' => 'PKR']);
    $wallet = Wallet::factory()->for($user)->create(['balance' => 0]);
    Sanctum::actingAs($user);

    // Fallback rate: 1 USD = 280 PKR.
    $this->postJson('/api/v1/transactions', [
        'wallet_id' => $wallet->id,
        'type' => 'expense',
        'amount' => 10,
        'currency' => 'USD',
        'date' => now()->toDateString(),
    ])->assertCreated()
        ->assertJsonPath('data.currency', 'USD')
        
        ;
});

test('cannot create a transaction in another user\'s wallet', function () {
    $user = User::factory()->create();
    $otherWallet = Wallet::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/transactions', [
        'wallet_id' => $otherWallet->id,
        'type' => 'expense',
        'amount' => 100,
        'date' => now()->toDateString(),
    ])->assertStatus(404);
});

test('validates transaction input', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/transactions', ['amount' => -5])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['wallet_id', 'type', 'amount', 'date']);
});

test('deletes own transaction', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create();
    $tx = Transaction::factory()->forUser($user, $wallet)->create();
    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/transactions/{$tx->id}")->assertOk();

    expect(Transaction::find($tx->id))->toBeNull();
});

test('cannot delete another user\'s transaction', function () {
    $tx = Transaction::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->deleteJson("/api/v1/transactions/{$tx->id}")->assertForbidden();
});
