<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function index(): Response
    {
        $wallets = Auth::user()->wallets()
            ->withCount('transactions')
            ->orderByDesc('is_default')
            ->get();

        return Inertia::render('Wallets/Index', [
            'wallets' => $wallets,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:cash,bank,card,crypto,other',
            'currency' => ['required', 'string', Rule::in(array_keys(config('currencies.supported')))],
            'balance' => 'required|numeric',
            'color' => 'required|string|size:7',
            'icon' => 'required|string|max:50',
            'is_default' => 'boolean',
        ]);

        if (! empty($validated['is_default'])) {
            Auth::user()->wallets()->update(['is_default' => false]);
        }

        Auth::user()->wallets()->create($validated);

        return back()->with('success', 'Wallet created.');
    }

    public function update(Request $request, Wallet $wallet): RedirectResponse
    {
        $this->authorize('update', $wallet);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:cash,bank,card,crypto,other',
            'currency' => ['required', 'string', Rule::in(array_keys(config('currencies.supported')))],
            'color' => 'required|string|size:7',
            'icon' => 'required|string|max:50',
            'is_default' => 'boolean',
        ]);

        if (! empty($validated['is_default'])) {
            Auth::user()->wallets()->update(['is_default' => false]);
        }

        $wallet->update($validated);

        return back()->with('success', 'Wallet updated.');
    }

    public function destroy(Wallet $wallet): RedirectResponse
    {
        $this->authorize('delete', $wallet);
        $wallet->update(['is_active' => false]);

        return back()->with('success', 'Wallet archived.');
    }
}
