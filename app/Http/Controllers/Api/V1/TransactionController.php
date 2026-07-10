<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TransactionController extends Controller
{
    /**
     * Paginated, filterable list of the user's transactions.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $transactions = $request->user()->transactions()
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->wallet_id, fn ($q) => $q->where('wallet_id', $request->wallet_id))
            ->when($request->search, fn ($q) => $q->where('description', 'like', "%{$request->search}%"))
            ->when($request->date_from, fn ($q) => $q->whereDate('date', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('date', '<=', $request->date_to))
            ->with(['category:id,name,color,icon,type,user_id', 'wallet:id,name,currency', 'merchant:id,name'])
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return TransactionResource::collection($transactions);
    }

    /**
     * Create a transaction, converting to the user's default currency
     * and adjusting the wallet balance.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => ['required', 'exists:wallets,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'type' => ['required', 'in:expense,income,transfer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:10'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        // The wallet must belong to the user.
        Gate::authorize('update', $user->wallets()->findOrFail($validated['wallet_id']));

        $txCurrency = strtoupper($validated['currency'] ?? $user->default_currency ?? 'PKR');
        $defaultCurrency = strtoupper($user->default_currency ?? 'PKR');

        $validated['currency'] = $txCurrency;

        $transaction = $user->transactions()->create($validated);
        $this->updateWalletBalance($transaction);

        $transaction->load(['category:id,name,color,icon,type,user_id', 'wallet:id,name,currency', 'merchant:id,name']);

        return (new TransactionResource($transaction))->response()->setStatusCode(201);
    }

    /**
     * Delete a transaction.
     */
    public function destroy(Transaction $transaction): JsonResponse
    {
        Gate::authorize('delete', $transaction);

        $transaction->delete();

        return response()->json(['message' => 'Transaction deleted.']);
    }

    private function updateWalletBalance(Transaction $transaction): void
    {
        $delta = $transaction->type === 'expense' ? -$transaction->amount : $transaction->amount;
        $transaction->wallet()->increment('balance', $delta);
    }
}
