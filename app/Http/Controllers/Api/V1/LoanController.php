<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoanResource;
use App\Models\Loan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LoanController extends Controller
{
    /**
     * List loans with lent/borrowed summary totals.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $loans = $user->loans()
            ->orderByRaw('settled_at IS NOT NULL')
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->get();

        $unsettled = $loans->whereNull('settled_at');

        return response()->json([
            'data' => LoanResource::collection($loans),
            'summary' => [
                'total_lent' => (float) $unsettled->where('type', 'lent')->sum(fn ($l) => $l->converted_amount ?? $l->amount),
                'total_borrowed' => (float) $unsettled->where('type', 'borrowed')->sum(fn ($l) => $l->converted_amount ?? $l->amount),
                'settled_count' => $loans->whereNotNull('settled_at')->count(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
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

        $user = $request->user();
        $currency = strtoupper($validated['currency'] ?? $user->default_currency ?? 'PKR');
        $defaultCurrency = strtoupper($user->default_currency ?? 'PKR');

        $validated['currency'] = $currency;
        

        $loan = $user->loans()->create($validated);

        return (new LoanResource($loan))->response()->setStatusCode(201);
    }

    /**
     * Toggle a loan between settled and unsettled.
     */
    public function settle(Loan $loan): LoanResource
    {
        Gate::authorize('update', $loan);

        $loan->update(['settled_at' => $loan->settled_at ? null : now()]);

        return new LoanResource($loan->refresh());
    }

    public function destroy(Loan $loan): JsonResponse
    {
        Gate::authorize('delete', $loan);
        $loan->delete();

        return response()->json(['message' => 'Loan deleted.']);
    }
}
