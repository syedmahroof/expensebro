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

        $totalExpenses = (float) ($user->transactions()
            ->where('type', 'expense')
            ->whereDate('date', '>=', $thisMonth)
            ->selectRaw('SUM(COALESCE(converted_amount, amount)) as total')
            ->value('total') ?? 0);

        $totalIncome = (float) ($user->transactions()
            ->where('type', 'income')
            ->whereDate('date', '>=', $thisMonth)
            ->selectRaw('SUM(COALESCE(converted_amount, amount)) as total')
            ->value('total') ?? 0);

        $lastMonthExpenses = (float) ($user->transactions()
            ->where('type', 'expense')
            ->whereDate('date', '>=', $lastMonth)
            ->whereDate('date', '<=', $lastMonthEnd)
            ->selectRaw('SUM(COALESCE(converted_amount, amount)) as total')
            ->value('total') ?? 0);

        $netWorth = (float) $user->wallets()->where('is_active', true)->sum('balance');

        $categoryBreakdown = $user->transactions()
            ->with('category:id,name,color')
            ->where('type', 'expense')
            ->whereDate('date', '>=', $thisMonth)
            ->selectRaw('category_id, SUM(COALESCE(converted_amount, amount)) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category ? [
                    'id' => $row->category->id,
                    'name' => $row->category->name,
                    'color' => $row->category->color,
                ] : null,
                'total' => (float) $row->total,
            ]);

        $txCount = $user->transactions()->whereDate('date', '>=', $thisMonth)->count();
        $daysIntoMonth = now()->day;

        return response()->json([
            'currency' => $user->default_currency ?? 'PKR',
            'wallets' => WalletResource::collection($wallets),
            'recent_transactions' => TransactionResource::collection($recentTransactions),
            'stats' => [
                'total_expenses' => $totalExpenses,
                'total_income' => $totalIncome,
                'balance' => $totalIncome - $totalExpenses,
                'net_worth' => $netWorth,
                'savings_rate' => $totalIncome > 0 ? round((($totalIncome - $totalExpenses) / $totalIncome) * 100, 1) : 0,
                'avg_daily_spend' => $daysIntoMonth > 0 ? round($totalExpenses / $daysIntoMonth, 0) : 0,
                'tx_count' => $txCount,
                'expense_change' => $lastMonthExpenses > 0
                    ? round((($totalExpenses - $lastMonthExpenses) / $lastMonthExpenses) * 100, 1)
                    : 0,
            ],
            'category_breakdown' => $categoryBreakdown,
        ]);
    }
}
