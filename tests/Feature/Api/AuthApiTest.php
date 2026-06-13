<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('a user can register and receives a token plus a default wallet', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Mobile User',
        'email' => 'mobile@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'device_name' => 'iPhone 15',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'needs_currency_setup']])
        ->assertJsonPath('user.email', 'mobile@example.com')
        ->assertJsonPath('user.needs_currency_setup', true);

    $user = User::where('email', 'mobile@example.com')->firstOrFail();
    expect($user->wallets()->where('is_default', true)->exists())->toBeTrue()
        ->and($user->tokens()->count())->toBe(1);
});

test('registration validates input', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('a user can log in with valid credentials', function () {
    $user = User::factory()->create(['password' => 'password']);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'Pixel 9',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'email']])
        ->assertJsonPath('user.id', $user->id);
});

test('login fails with invalid credentials', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('the me endpoint returns the authenticated user', function () {
    $user = User::factory()->create(['default_currency' => 'INR']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.default_currency', 'INR')
        ->assertJsonPath('data.needs_currency_setup', false);
});

test('unauthenticated requests to protected endpoints are rejected', function () {
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

test('a user can log out which revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mobile')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    expect($user->tokens()->count())->toBe(0);
});

test('currencies endpoint is public and includes INR', function () {
    $this->getJson('/api/v1/currencies')
        ->assertOk()
        ->assertJsonPath('currencies.INR', 'Indian Rupee')
        ->assertJsonStructure(['currencies', 'locales']);
});

test('a user can set their currency through preferences', function () {
    $user = User::factory()->create(['default_currency' => null]);
    $wallet = $user->wallets()->create([
        'name' => 'Cash', 'type' => 'cash', 'currency' => 'PKR',
        'balance' => 0, 'color' => '#10b981', 'icon' => 'wallet',
        'is_default' => true, 'is_active' => true,
    ]);
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/preferences', ['default_currency' => 'INR'])
        ->assertOk()
        ->assertJsonPath('data.default_currency', 'INR');

    expect($wallet->refresh()->currency)->toBe('INR');
});

test('preferences rejects an unsupported currency', function () {
    $user = User::factory()->create(['default_currency' => null]);
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/preferences', ['default_currency' => 'XXX'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('default_currency');
});
