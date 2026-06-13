<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\WalletResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Aggregated dashboard payload for the current month.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $wallets = $user->wallets()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->get();

        $recentTransactions = $user->transactions()
            ->with(['category:id,name,color,icon,type,user_id', 'wallet:id,name,currency', 'merchant:id,name'])
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $totalExpenses = $user->transactions()
            ->where('type', 'expense')
            ->whereDate('date', '>=', $thisMonth)
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        $totalIncome = $user->transactions()
            ->where('type', 'income')
            ->whereDate('date', '>=', $thisMonth)
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        $lastMonthExpenses = $user->transactions()
            ->where('type', 'expense')
            ->whereDate('date', '>=', $lastMonth)
            ->whereDate('date', '<=', $lastMonthEnd)
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        $netWorth = $user->wallets()->where('is_active', true)->selectRaw('currency, SUM(balance) as total')->groupBy('currency')->pluck('total', 'currency')->toArray();

        $categoryBreakdown = $user->transactions()
            ->with('category:id,name,color')
            ->where('type', 'expense')
            ->whereDate('date', '>=', $thisMonth)
            ->selectRaw('category_id, currency, SUM(amount) as total')->groupBy('category_id', 'currency')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category ? [
                    'id' => $row->category->id,
                    'name' => $row->category->name,
                    'color' => $row->category->color,
                ] : null,
                'currency' => $row->currency, 'total' => (float) $row->total,
            ]);

        $txCount = $user->transactions()->whereDate('date', '>=', $thisMonth)->count();
        $daysIntoMonth = now()->day;

        $avgDailySpend = [];
        $balance = [];
        $expenseChange = [];
        $savingsRate = [];

        $allCurrencies = collect(array_keys($totalIncome))->merge(array_keys($totalExpenses))->unique();
        foreach ($allCurrencies as $c) {
            $inc = $totalIncome[$c] ?? 0;
            $exp = $totalExpenses[$c] ?? 0;
            $balance[$c] = $inc - $exp;
            $savingsRate[$c] = $inc > 0 ? round((($inc - $exp) / $inc) * 100, 1) : 0;
            $avgDailySpend[$c] = $daysIntoMonth > 0 ? round($exp / $daysIntoMonth, 0) : 0;

            $lastExp = $lastMonthExpenses[$c] ?? 0;
            $expenseChange[$c] = $lastExp > 0 ? round((($exp - $lastExp) / $lastExp) * 100, 1) : 0;
        }

        return response()->json([
            'currency' => $user->default_currency ?? 'PKR',
            'wallets' => WalletResource::collection($wallets),
            'recent_transactions' => TransactionResource::collection($recentTransactions),
            'stats' => [
                'total_expenses' => $totalExpenses,
                'total_income' => $totalIncome,
                'balance' => $balance,
                'net_worth' => $netWorth,
                'savings_rate' => $savingsRate,
                'avg_daily_spend' => $avgDailySpend,
                'tx_count' => $txCount,
                'expense_change' => $expenseChange,
            ],
            'category_breakdown' => $categoryBreakdown,
        ]);
    }
}
