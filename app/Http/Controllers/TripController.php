<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TripController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        $trips = $user->trips()
            ->withCount('transactions')
            ->withSum(['transactions as total_spent' => fn ($q) => $q->where('type', 'expense')], 'amount')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Trips/Index', [
            'trips' => $trips,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ]);

        Auth::user()->trips()->create($validated);

        return back();
    }

    public function show(Trip $trip): Response
    {
        Gate::authorize('view', $trip);

        $trip->load('transactions.category', 'transactions.merchant', 'transactions.wallet');

        $transactions = $trip->transactions()->orderByDesc('date')->get();

        $totalSpent = $transactions->where('type', 'expense')->sum('amount');
        $totalIncome = $transactions->where('type', 'income')->sum('amount');

        $dailySpend = $transactions
            ->where('type', 'expense')
            ->groupBy(fn ($t) => $t->date->format('Y-m-d'))
            ->map(fn ($group, $date) => [
                'date' => $date,
                'label' => \Carbon\Carbon::parse($date)->format('d M'),
                'amount' => $group->sum('amount'),
            ])
            ->sortKeys()
            ->values();

        $categoryBreakdown = $transactions
            ->where('type', 'expense')
            ->groupBy('category_id')
            ->map(function ($group) use ($totalSpent) {
                $first = $group->first();
                $amount = $group->sum('amount');

                return [
                    'category_id' => $first->category_id,
                    'name' => $first->category?->name ?? 'Uncategorized',
                    'color' => $first->category?->color ?? '#6b7280',
                    'amount' => (float) $amount,
                    'count' => $group->count(),
                    'percentage' => $totalSpent > 0 ? round(($amount / $totalSpent) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('amount')
            ->values();

        $locationHotspots = $transactions
            ->whereNotNull('location')
            ->where('type', 'expense')
            ->groupBy('location')
            ->map(fn ($group, $location) => [
                'location' => $location,
                'amount' => (float) $group->sum('amount'),
                'count' => $group->count(),
            ])
            ->sortByDesc('amount')
            ->values();

        $daysActive = $trip->start_date && $trip->end_date
            ? max(1, $trip->start_date->diffInDays($trip->end_date) + 1)
            : max(1, $transactions->isNotEmpty()
                ? $transactions->min('date')->diffInDays($transactions->max('date')) + 1
                : 1);

        $dailySafeSpend = $trip->budget && $totalSpent < $trip->budget
            ? round(($trip->budget - $totalSpent) / max(1, $daysActive), 2)
            : 0;

        return Inertia::render('Trips/Show', [
            'trip' => $trip->only('id', 'name', 'destination', 'budget', 'currency', 'start_date', 'end_date', 'status', 'notes'),
            'transactions' => $transactions->map(fn ($t) => [
                'id' => $t->id,
                'description' => $t->description,
                'amount' => (float) $t->amount,
                'currency' => $t->currency,
                'type' => $t->type,
                'date' => $t->date->format('Y-m-d'),
                'location' => $t->location,
                'category' => $t->category ? ['name' => $t->category->name, 'color' => $t->category->color] : null,
                'merchant' => $t->merchant ? ['name' => $t->merchant->name] : null,
                'wallet' => $t->wallet ? ['name' => $t->wallet->name] : null,
            ]),
            'stats' => [
                'totalSpent' => (float) $totalSpent,
                'totalIncome' => (float) $totalIncome,
                'budget' => $trip->budget ? (float) $trip->budget : null,
                'remaining' => $trip->budget ? max(0, (float) $trip->budget - (float) $totalSpent) : null,
                'dailySafeSpend' => $dailySafeSpend,
                'transactionCount' => $transactions->count(),
                'daysActive' => $daysActive,
            ],
            'dailySpend' => $dailySpend,
            'categoryBreakdown' => $categoryBreakdown,
            'locationHotspots' => $locationHotspots,
        ]);
    }

    public function update(Request $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('update', $trip);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'in:active,completed,archived'],
            'notes' => ['nullable', 'string'],
        ]);

        $trip->update($validated);

        return back();
    }

    public function destroy(Trip $trip): RedirectResponse
    {
        Gate::authorize('delete', $trip);

        $trip->delete();

        return to_route('trips.index');
    }
}
