<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;

test('newly registered user has no default currency and is prompted to set one', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect();

    $user = User::where('email', 'test@example.com')->firstOrFail();
    expect($user->default_currency)->toBeNull();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('needsCurrencySetup', true)
            ->has('currencies.INR'));
});

test('user can set default currency after registration', function () {
    $user = User::factory()->create(['default_currency' => null]);
    $wallet = Wallet::factory()->default()->for($user)->create(['currency' => 'PKR']);

    $this->actingAs($user)
        ->post('/onboarding/currency', ['default_currency' => 'INR'])
        ->assertRedirect();

    expect($user->refresh()->default_currency)->toBe('INR')
        ->and($wallet->refresh()->currency)->toBe('INR');
});

test('setting onboarding currency does not change wallets that already have transactions', function () {
    $user = User::factory()->create(['default_currency' => null]);
    $wallet = Wallet::factory()->for($user)->create(['currency' => 'PKR']);
    Transaction::factory()->for($user)->for($wallet)->create();

    $this->actingAs($user)
        ->post('/onboarding/currency', ['default_currency' => 'EUR'])
        ->assertRedirect();

    expect($user->refresh()->default_currency)->toBe('EUR')
        ->and($wallet->refresh()->currency)->toBe('PKR');
});

test('unsupported currency is rejected', function () {
    $user = User::factory()->create(['default_currency' => null]);

    $this->actingAs($user)
        ->post('/onboarding/currency', ['default_currency' => 'XXX'])
        ->assertSessionHasErrors('default_currency');

    expect($user->refresh()->default_currency)->toBeNull();
});

test('user with a currency set is not prompted again', function () {
    $user = User::factory()->create(['default_currency' => 'INR']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('needsCurrencySetup', false)
            ->where('userCurrency', 'INR'));
});
