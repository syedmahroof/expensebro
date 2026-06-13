<?php

use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use App\Models\Wallet;
use Laravel\Sanctum\Sanctum;

test('lists the user\'s trips with counts', function () {
    $user = User::factory()->create();
    Trip::factory()->for($user)->create(['name' => 'Mine']);
    Trip::factory()->create(['name' => 'Someone else']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/trips')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Mine')
        ->assertJsonStructure(['data' => [['id', 'name', 'transactions_count', 'total_spent']]]);
});

test('creates a trip', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/trips', [
        'name' => 'Dubai 2026',
        'destination' => 'Dubai',
        'budget' => 5000,
        'currency' => 'AED',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-10',
    ])->assertCreated()->assertJsonPath('data.name', 'Dubai 2026');

    expect($user->trips()->where('name', 'Dubai 2026')->exists())->toBeTrue();
});

test('validates that end date is after start date', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/trips', [
        'name' => 'Bad', 'start_date' => '2026-07-10', 'end_date' => '2026-07-01',
    ])->assertStatus(422)->assertJsonValidationErrors('end_date');
});

test('shows a trip with stats and breakdown', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create();
    $trip = Trip::factory()->for($user)->create(['budget' => 1000]);
    Transaction::factory()->forUser($user, $wallet)->create([
        'trip_id' => $trip->id, 'type' => 'expense', 'amount' => 300,
        'date' => now()->toDateString(),
    ]);
    Sanctum::actingAs($user);

    $this->getJson("/api/v1/trips/{$trip->id}")
        ->assertOk()
        ->assertJsonPath('stats.total_spent', 300)
        ->assertJsonPath('stats.remaining', 700)
        ->assertJsonPath('trip.id', $trip->id)
        ->assertJsonCount(1, 'transactions')
        ->assertJsonStructure([
            'trip' => ['id', 'name'],
            'stats',
            'category_breakdown',
            'transactions' => [['id', 'type', 'amount']],
        ]);
});

test('cannot view another user\'s trip', function () {
    $trip = Trip::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/trips/{$trip->id}")->assertForbidden();
});

test('updates a trip', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->putJson("/api/v1/trips/{$trip->id}", ['status' => 'completed'])
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');
});

test('deletes a trip', function () {
    $user = User::factory()->create();
    $trip = Trip::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/trips/{$trip->id}")->assertOk();
    expect(Trip::find($trip->id))->toBeNull();
});
