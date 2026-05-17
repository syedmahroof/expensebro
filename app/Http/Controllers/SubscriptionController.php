<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __invoke(): Response
    {
        $user = Auth::user();

        $paymentHistory = $user->paymentLogs()
            ->orderByDesc('paid_at')
            ->get(['id', 'plan', 'amount', 'currency', 'status', 'invoice_id', 'paid_at']);

        return Inertia::render('Subscription', [
            'currentPlan' => $user->plan,
            'planExpiresAt' => $user->plan_expires_at,
            'paymentHistory' => $paymentHistory,
            'plans' => [
                [
                    'id' => 'free',
                    'name' => 'Free',
                    'price' => 0,
                    'period' => 'forever',
                    'tagline' => 'For personal use',
                    'features' => [
                        'Manual transaction entry',
                        '2 wallets',
                        'Basic categories',
                        '50 transactions / month',
                        'WhatsApp OTP login',
                    ],
                    'locked' => [
                        'AI Chat logging',
                        'Receipt scanner',
                        'CSV export',
                        'AI spending insights',
                        'Unlimited wallets',
                    ],
                    'cta' => 'Current plan',
                    'highlighted' => false,
                ],
                [
                    'id' => 'now',
                    'name' => 'Now',
                    'price' => 1,
                    'period' => 'month',
                    'tagline' => 'Everything, for just $1',
                    'features' => [
                        'Unlimited transactions',
                        'Unlimited wallets',
                        'AI Chat logging',
                        'Receipt scanner',
                        'CSV export',
                        'AI spending insights',
                        'Priority support',
                    ],
                    'locked' => [],
                    'cta' => 'Upgrade for $1/mo',
                    'highlighted' => true,
                ],
                [
                    'id' => 'family',
                    'name' => 'Family',
                    'price' => 5,
                    'period' => 'month',
                    'tagline' => 'For you and yours',
                    'features' => [
                        'Everything in Now',
                        'Up to 5 family members',
                        'Shared wallets & budgets',
                        'Custom categories',
                        'Advanced export options',
                        'Early access to beta features',
                    ],
                    'locked' => [],
                    'cta' => 'Upgrade for $5/mo',
                    'highlighted' => false,
                ],
            ],
        ]);
    }
}
