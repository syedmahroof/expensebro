<?php

namespace App\Listeners;

use App\Models\User;
use Laravel\Cashier\Events\WebhookHandled;

class SyncPlanOnSubscription
{
    /**
     * Handle the event.
     */
    public function handle(WebhookHandled $event): void
    {
        $payload = $event->payload;

        if ($payload['type'] === 'customer.subscription.created' || $payload['type'] === 'customer.subscription.updated') {
            $stripeId = $payload['data']['object']['customer'];
            $user = User::where('stripe_id', $stripeId)->first();

            if ($user) {
                // Determine plan from price ID or metadata
                $priceId = $payload['data']['object']['items']['data'][0]['price']['id'];

                $plan = match ($priceId) {
                    config('services.stripe.price_now') => 'now',
                    config('services.stripe.price_family') => 'family',
                    default => 'free',
                };

                $user->update([
                    'plan' => $plan,
                    'plan_expires_at' => now()->addMonth(), // Basic sync
                ]);
            }
        }

        if ($payload['type'] === 'customer.subscription.deleted') {
            $stripeId = $payload['data']['object']['customer'];
            $user = User::where('stripe_id', $stripeId)->first();

            if ($user) {
                $user->update([
                    'plan' => 'free',
                    'plan_expires_at' => null,
                ]);
            }
        }
    }
}
