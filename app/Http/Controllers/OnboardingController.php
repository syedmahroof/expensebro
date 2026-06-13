<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OnboardingController extends Controller
{
    /**
     * Save the currency chosen right after registration and apply it
     * to wallets that have no transactions yet (e.g. the auto-created one).
     */
    public function storeCurrency(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_currency' => ['required', 'string', Rule::in(array_keys(config('currencies.supported')))],
        ]);

        $user = $request->user();
        $user->update(['default_currency' => $validated['default_currency']]);

        $user->wallets()
            ->doesntHave('transactions')
            ->update(['currency' => $validated['default_currency']]);

        return back()->with('status', 'currency-set');
    }
}
