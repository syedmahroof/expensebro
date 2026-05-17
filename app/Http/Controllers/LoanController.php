<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Services\CurrencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();
        $defaultCurrency = $user->default_currency ?? 'PKR';

        $loans = $user->loans()
            ->orderByRaw('settled_at IS NOT NULL')
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->get();

        $lent = $loans->where('type', 'lent');
        $borrowed = $loans->where('type', 'borrowed');

        return Inertia::render('Loans/Index', [
            'lent' => $lent->values(),
            'borrowed' => $borrowed->values(),
            'summary' => [
                'totalLent' => (float) $lent->where('settled_at', null)->sum(fn ($l) => $l->converted_amount ?? $l->amount),
                'totalBorrowed' => (float) $borrowed->where('settled_at', null)->sum(fn ($l) => $l->converted_amount ?? $l->amount),
                'settledCount' => $loans->whereNotNull('settled_at')->count(),
            ],
            'defaultCurrency' => $defaultCurrency,
            'currencies' => CurrencyService::supported(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'type' => ['required', 'in:lent,borrowed'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:10'],
            'description' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $user = Auth::user();
        $currency = strtoupper($validated['currency'] ?? $user->default_currency ?? 'PKR');
        $defaultCurrency = strtoupper($user->default_currency ?? 'PKR');

        $validated['currency'] = $currency;
        $validated['converted_amount'] = $currency !== $defaultCurrency
            ? CurrencyService::convert((float) $validated['amount'], $currency, $defaultCurrency)
            : $validated['amount'];

        $user->loans()->create($validated);

        return back();
    }

    public function settle(Loan $loan): RedirectResponse
    {
        Gate::authorize('update', $loan);

        $loan->update(['settled_at' => $loan->settled_at ? null : now()]);

        return back();
    }

    public function destroy(Loan $loan): RedirectResponse
    {
        Gate::authorize('delete', $loan);

        $loan->delete();

        return back();
    }
}
