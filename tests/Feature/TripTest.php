<?php

use App\Models\Trip;
use App\Models\User;

test('authenticated user can view trips index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/trips')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Trips/Index')->has('trips'));
});

test('user can create a trip', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/trips', [
            'name' => 'Dubai 2026',
            'destination' => 'Dubai, UAE',
            'budget' => 5000,
            'currency' => 'AED',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-10',
        ])
        ->assertRedirect();

    expect($user->trips()->where('name', 'Dubai 2026')->exists())->toBeTrue();
});

test('user can view their own trip', function () {
    $user = User::factory()->create();
    $trip = $user->trips()->create(['name' => 'Test Trip', 'currency' => 'PKR']);

    $this->actingAs($user)
        ->get("/trips/{$trip->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Trips/Show')->has('trip'));
});

test('user cannot view another users trip', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $trip = $owner->trips()->create(['name' => 'Private Trip', 'currency' => 'PKR']);

    $this->actingAs($other)
        ->get("/trips/{$trip->id}")
        ->assertForbidden();
});

test('user can delete their own trip', function () {
    $user = User::factory()->create();
    $trip = $user->trips()->create(['name' => 'To Delete', 'currency' => 'PKR']);

    $this->actingAs($user)
        ->delete("/trips/{$trip->id}")
        ->assertRedirect('/trips');

    expect(Trip::find($trip->id))->toBeNull();
});
