<?php

use App\Models\AppNotification;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('guests are redirected from notifications page', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));
});

test('authenticated user sees the notifications Inertia page', function () {
    $user = User::factory()->create();

    AppNotification::create([
        'user_id' => $user->id,
        'title' => 'Welcome!',
        'body' => 'Thanks for joining.',
        'type' => 'info',
        'read' => false,
    ]);

    actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notifications/Index')
            ->has('notifications.data', 1)
            ->where('unreadCount', 1)
        );
});

test('bell dropdown still gets JSON when using XMLHttpRequest header', function () {
    $user = User::factory()->create();

    AppNotification::create([
        'user_id' => $user->id,
        'title' => 'Test',
        'body' => 'Body.',
        'type' => 'success',
        'read' => false,
    ]);

    actingAs($user)
        ->get(route('notifications.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonStructure(['notifications', 'unread_count']);
});

test('mark all read redirects back from the page', function () {
    $user = User::factory()->create();

    AppNotification::create([
        'user_id' => $user->id,
        'title' => 'Unread',
        'body' => 'Body.',
        'type' => 'info',
        'read' => false,
    ]);

    actingAs($user)
        ->post(route('notifications.read'), ['ids' => []])
        ->assertRedirect();

    expect(AppNotification::where('user_id', $user->id)->where('read', false)->count())->toBe(0);
});

test('user only sees their own notifications', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    AppNotification::create(['user_id' => $other->id, 'title' => 'Other', 'body' => '.', 'type' => 'info', 'read' => false]);

    actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('notifications.total', 0));
});
