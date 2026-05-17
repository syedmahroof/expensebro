<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionCheckoutController extends Controller
{
    /**
     * Redirect to Stripe Checkout for a subscription.
     */
    public function checkout(Request $request)
    {
        $plan = $request->input('plan');
        $user = Auth::user();

        // Map plan IDs to Stripe Price IDs from .env
        $priceId = match ($plan) {
            'now' => config('services.stripe.price_now'),
            'family' => config('services.stripe.price_family'),
            default => null,
        };

        if (!$priceId) {
            return back()->withErrors(['message' => 'Invalid plan selected.']);
        }

        return $user->newSubscription($plan, $priceId)
            ->checkout([
                'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('subscription'),
            ]);
    }

    /**
     * Handle successful payment redirection.
     */
    public function success(Request $request)
    {
        return redirect()->route('subscription')->with('success', 'Thank you for your subscription! Your plan will be updated shortly.');
    }

    /**
     * Redirect to Stripe Billing Portal.
     */
    public function portal(Request $request)
    {
        return $request->user()->redirectToBillingPortal(
            route('subscription')
        );
    }
}
