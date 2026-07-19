<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class WalletController extends Controller
{
    /**
     * List the authenticated user's active wallets.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $wallets = $request->user()->wallets()
            ->where('is_active', true)
            ->withCount('transactions')
            ->orderByDesc('is_default')
            ->get();

        return WalletResource::collection($wallets);
    }

    /**
     * Create a new wallet.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateWallet($request, isUpdate: false);

        if (! empty($validated['is_default'])) {
            $request->user()->wallets()->update(['is_default' => false]);
        }

        $wallet = $request->user()->wallets()->create($validated);

        return (new WalletResource($wallet))->response()->setStatusCode(201);
    }

    /**
     * Update an existing wallet.
     */
    public function update(Request $request, Wallet $wallet): WalletResource
    {
        Gate::authorize('update', $wallet);

        $validated = $this->validateWallet($request, isUpdate: true);

        if (! empty($validated['is_default'])) {
            $request->user()->wallets()->update(['is_default' => false]);
        }

        if (isset($validated['balance']) && $validated['balance'] != $wallet->balance) {
            $difference = $validated['balance'] - $wallet->balance;

            $request->user()->transactions()->create([
                'wallet_id' => $wallet->id,
                'type' => $difference > 0 ? 'income' : 'expense',
                'amount' => abs($difference),
                'currency' => $wallet->currency,
                'description' => 'Wallet balance adjustment',
                'date' => now(),
            ]);
        }

        $wallet->update($validated);

        return new WalletResource($wallet->refresh());
    }

    /**
     * Archive a wallet (soft removal from the active list).
     */
    public function destroy(Request $request, Wallet $wallet): JsonResponse
    {
        Gate::authorize('delete', $wallet);

        $wallet->update(['is_active' => false]);

        return response()->json(['message' => 'Wallet archived.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateWallet(Request $request, bool $isUpdate): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:cash,bank,card,crypto,other'],
            'currency' => ['required', 'string', Rule::in(array_keys(config('currencies.supported')))],
            'balance' => [$isUpdate ? 'sometimes' : 'required', 'numeric'],
            'color' => ['required', 'string', 'size:7'],
            'icon' => ['required', 'string', 'max:50'],
            'is_default' => ['boolean'],
        ]);
    }
}
