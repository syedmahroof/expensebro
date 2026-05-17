<?php

use App\Models\User;
use App\Models\Wallet;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    $this->fakeUser = (new SocialiteUser)->map([
        'id' => 'google-uid-123',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);
});

test('social redirect returns a redirect response', function () {
    Socialite::fake('google');

    $this->get(route('social.redirect', 'google'))
        ->assertRedirect();
});

test('invalid provider returns 404', function () {
    $this->get(route('social.redirect', 'twitter'))->assertNotFound();
    $this->get(route('social.callback', 'twitter'))->assertNotFound();
});

test('new user is created and logged in on first social login', function () {
    Socialite::fake('google', $this->fakeUser);

    $this->get(route('social.callback', 'google'))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();

    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
        'social_provider' => 'google',
        'social_id' => 'google-uid-123',
    ]);
});

test('new social user gets a default wallet', function () {
    Socialite::fake('google', $this->fakeUser);

    $this->get(route('social.callback', 'google'));

    $user = User::where('social_id', 'google-uid-123')->first();

    expect(Wallet::where('user_id', $user->id)->count())->toBe(1);
});

test('existing social user is logged in without duplicate', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'social_provider' => 'google',
        'social_id' => 'google-uid-123',
    ]);

    Socialite::fake('google', $this->fakeUser);

    $this->get(route('social.callback', 'google'))
        ->assertRedirect(route('dashboard'));

    expect(User::where('social_id', 'google-uid-123')->count())->toBe(1);
});

test('failed social callback redirects to login with error', function () {
    Socialite::shouldReceive('driver->user')->andThrow(new Exception('OAuth denied'));

    $this->get(route('social.callback', 'google'))
        ->assertRedirect(route('login'));
});

test('facebook and github redirects work', function () {
    foreach (['facebook', 'github'] as $provider) {
        Socialite::fake($provider);
        $this->get(route('social.redirect', $provider))->assertRedirect();
    }
});
