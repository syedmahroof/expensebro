<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TripController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $trips = $request->user()->trips()
            ->withCount('transactions')
            ->withSum(['transactions as total_spent' => fn ($q) => $q->where('type', 'expense')], 'amount')
            ->orderByDesc('created_at')
            ->get();

        return TripResource::collection($trips);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateTrip($request);

        $trip = $request->user()->trips()->create($validated);

        return (new TripResource($trip))->response()->setStatusCode(201);
    }

    /**
     * Trip detail with spending stats and breakdowns.
     */
    public function show(Trip $trip): JsonResponse
    {
        Gate::authorize('view', $trip);

        $transactions = $trip->transactions()
            ->with(['category:id,name,color,icon,type,user_id', 'merchant:id,name', 'wallet:id,name,currency'])
            ->orderByDesc('date')
            ->get();

        $expenses = $transactions->where('type', 'expense');
        $totalSpent = (float) $expenses->sum('amount');
        $totalIncome = (float) $transactions->where('type', 'income')->sum('amount');

        $categoryBreakdown = $expenses
            ->groupBy('category_id')
            ->map(function ($group) use ($totalSpent) {
                $first = $group->first();
                $amount = (float) $group->sum('amount');

                return [
                    'name' => $first->category?->name ?? 'Uncategorized',
                    'color' => $first->category?->color ?? '#6b7280',
                    'amount' => $amount,
                    'count' => $group->count(),
                    'percentage' => $totalSpent > 0 ? round(($amount / $totalSpent) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('amount')
            ->values();

        $daysActive = $trip->start_date && $trip->end_date
            ? max(1, $trip->start_date->diffInDays($trip->end_date) + 1)
            : 1;

        return response()->json([
            'trip' => new TripResource($trip),
            'stats' => [
                'total_spent' => $totalSpent,
                'total_income' => $totalIncome,
                'budget' => $trip->budget !== null ? (float) $trip->budget : null,
                'remaining' => $trip->budget !== null ? max(0, (float) $trip->budget - $totalSpent) : null,
                'transaction_count' => $transactions->count(),
                'days_active' => $daysActive,
            ],
            'category_breakdown' => $categoryBreakdown,
            'transactions' => TransactionResource::collection($transactions),
        ]);
    }

    public function update(Request $request, Trip $trip): TripResource
    {
        Gate::authorize('update', $trip);

        $validated = $this->validateTrip($request, isUpdate: true);
        $trip->update($validated);

        return new TripResource($trip->refresh());
    }

    public function destroy(Trip $trip): JsonResponse
    {
        Gate::authorize('delete', $trip);
        $trip->delete();

        return response()->json(['message' => 'Trip deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTrip(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'in:active,completed,archived'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
