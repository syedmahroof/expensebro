<?php

use App\Models\Entity;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('lists the user\'s entities', function () {
    $user = User::factory()->create();
    Entity::factory()->for($user)->create(['name' => 'Honda']);
    Entity::factory()->create(['name' => 'Other']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/entities')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Honda')
        ->assertJsonStructure(['data' => [['id', 'name', 'type', 'transactions_count']]]);
});

test('creates an entity', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/entities', [
        'name' => 'Main Office',
        'type' => 'office',
        'color' => '#6366f1',
        'emoji' => '🏢',
    ])->assertCreated()->assertJsonPath('data.name', 'Main Office');

    expect($user->entities()->where('name', 'Main Office')->exists())->toBeTrue();
});

test('validates entity type', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/entities', [
        'name' => 'X', 'type' => 'spaceship', 'color' => '#fff',
    ])->assertStatus(422)->assertJsonValidationErrors('type');
});

test('updates an entity', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->putJson("/api/v1/entities/{$entity->id}", [
        'name' => 'Renamed', 'type' => 'vehicle', 'color' => '#10b981',
    ])->assertOk()->assertJsonPath('data.name', 'Renamed');
});

test('cannot update another user\'s entity', function () {
    $entity = Entity::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->putJson("/api/v1/entities/{$entity->id}", [
        'name' => 'Hack', 'type' => 'other', 'color' => '#000000',
    ])->assertForbidden();
});

test('deletes an entity', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/entities/{$entity->id}")->assertOk();
    expect(Entity::find($entity->id))->toBeNull();
});
