<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['transactions' => [], 'wallets' => [], 'categories' => []]);
        }

        $user = Auth::user();
        $like = "%{$q}%";

        $transactions = $user->transactions()
            ->with(['category:id,name,color', 'wallet:id,name'])
            ->where('description', 'like', $like)
            ->orderByDesc('date')
            ->limit(5)
            ->get(['id', 'description', 'amount', 'currency', 'type', 'date', 'category_id', 'wallet_id']);

        $wallets = $user->wallets()
            ->where('name', 'like', $like)
            ->limit(4)
            ->get(['id', 'name', 'type', 'balance', 'currency', 'color']);

        $categories = Category::where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))
            ->where('name', 'like', $like)
            ->limit(4)
            ->get(['id', 'name', 'color', 'type']);

        return response()->json([
            'transactions' => $transactions,
            'wallets' => $wallets,
            'categories' => $categories,
        ]);
    }
}
