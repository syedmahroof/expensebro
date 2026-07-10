<?php

use App\Models\Loan;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('lists loans with lent and borrowed summary', function () {
    $user = User::factory()->create(['default_currency' => 'PKR']);
    Loan::factory()->for($user)->lent()->create(['amount' => 1000, 'converted_amount' => 1000]);
    Loan::factory()->for($user)->borrowed()->create(['amount' => 400, 'converted_amount' => 400]);
    Loan::factory()->for($user)->lent()->settled()->create(['amount' => 999, 'converted_amount' => 999]);
    Loan::factory()->create(); // another user
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/loans')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('summary.total_lent', 1000)
        ->assertJsonPath('summary.total_borrowed', 400)
        ->assertJsonPath('summary.settled_count', 1);
});

test('creates a loan and converts the amount', function () {
    $user = User::factory()->create(['default_currency' => 'PKR']);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/loans', [
        'contact_name' => 'Ali',
        'type' => 'lent',
        'amount' => 10,
        'currency' => 'USD',
    ])->assertCreated()
        ->assertJsonPath('data.contact_name', 'Ali'); // 10 USD * 280
});

test('validates loan input', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/loans', ['amount' => 0])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['contact_name', 'type', 'amount']);
});

test('settle toggles the settled state', function () {
    $user = User::factory()->create();
    $loan = Loan::factory()->for($user)->create(['settled_at' => null]);
    Sanctum::actingAs($user);

    $this->patchJson("/api/v1/loans/{$loan->id}/settle")
        ->assertOk()
        ->assertJsonPath('data.is_settled', true);

    expect($loan->refresh()->settled_at)->not->toBeNull();

    $this->patchJson("/api/v1/loans/{$loan->id}/settle")
        ->assertOk()
        ->assertJsonPath('data.is_settled', false);
});

test('cannot settle another user\'s loan', function () {
    $loan = Loan::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->patchJson("/api/v1/loans/{$loan->id}/settle")->assertForbidden();
});

test('deletes a loan', function () {
    $user = User::factory()->create();
    $loan = Loan::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/loans/{$loan->id}")->assertOk();
    expect(Loan::find($loan->id))->toBeNull();
});
