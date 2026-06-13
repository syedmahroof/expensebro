<?php

use App\Models\User;
use App\Models\Wallet;
use Laravel\Sanctum\Sanctum;

function walletPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Savings',
        'type' => 'bank',
        'currency' => 'INR',
        'balance' => 1000,
        'color' => '#6366f1',
        'icon' => 'wallet',
        'is_default' => false,
    ], $overrides);
}

test('lists only the authenticated user\'s active wallets', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create(['name' => 'Active']);
    Wallet::factory()->for($user)->create(['name' => 'Archived', 'is_active' => false]);
    Wallet::factory()->create(['name' => 'Someone else']);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/wallets')->assertOk();

    $response->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Active')
        ->assertJsonStructure(['data' => [['id', 'name', 'balance', 'transactions_count']]]);
});

test('creates a wallet', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/wallets', walletPayload(['name' => 'Cash Box']))
        ->assertCreated()
        ->assertJsonPath('data.name', 'Cash Box')
        ->assertJsonPath('data.currency', 'INR');

    expect($user->wallets()->where('name', 'Cash Box')->exists())->toBeTrue();
});

test('setting a new default unsets the previous default', function () {
    $user = User::factory()->create();
    $old = Wallet::factory()->for($user)->create(['is_default' => true]);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/wallets', walletPayload(['is_default' => true]))->assertCreated();

    expect($old->refresh()->is_default)->toBeFalse();
});

test('validates wallet input', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/wallets', walletPayload(['currency' => 'XXX', 'type' => 'bad']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['currency', 'type']);
});

test('updates a wallet', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create(['name' => 'Old']);
    Sanctum::actingAs($user);

    $this->putJson("/api/v1/wallets/{$wallet->id}", walletPayload(['name' => 'New Name']))
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name');
});

test('cannot update another user\'s wallet', function () {
    $wallet = Wallet::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->putJson("/api/v1/wallets/{$wallet->id}", walletPayload())->assertForbidden();
});

test('archives a wallet', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/wallets/{$wallet->id}")->assertOk();

    expect($wallet->refresh()->is_active)->toBeFalse();
});

test('wallet endpoints require authentication', function () {
    $this->getJson('/api/v1/wallets')->assertUnauthorized();
});
