<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\RecurringDetectionService;
use App\Services\SpendingInsightService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = Auth::user();

        $wallets = $user->wallets()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->get(['id', 'name', 'type', 'balance', 'currency', 'color', 'icon']);

        $recentTransactions = $user->transactions()
            ->with(['category:id,name,color,icon', 'wallet:id,name', 'merchant:id,name'])
            ->orderByDesc('date')
            ->limit(10)
            ->get(['id', 'type', 'amount', 'currency', 'description', 'date', 'category_id', 'wallet_id', 'merchant_id']);

        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $totalExpenses = $user->transactions()
            ->where('type', 'expense')
            ->whereDate('date', '>=', $thisMonth)
            ->selectRaw('SUM(COALESCE(converted_amount, amount)) as total')
            ->value('total') ?? 0;

        $totalIncome = $user->transactions()
            ->where('type', 'income')
            ->whereDate('date', '>=', $thisMonth)
            ->selectRaw('SUM(COALESCE(converted_amount, amount)) as total')
            ->value('total') ?? 0;

        $lastMonthExpenses = $user->transactions()
            ->where('type', 'expense')
            ->whereDate('date', '>=', $lastMonth)
            ->whereDate('date', '<=', $lastMonthEnd)
            ->selectRaw('SUM(COALESCE(converted_amount, amount)) as total')
            ->value('total') ?? 0;

        $netWorth = $user->wallets()->where('is_active', true)->sum('balance');

        $categoryBreakdown = $user->transactions()
            ->with('category:id,name,color')
            ->where('type', 'expense')
            ->whereDate('date', '>=', $thisMonth)
            ->selectRaw('category_id, SUM(COALESCE(converted_amount, amount)) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $txCountThisMonth = $user->transactions()
            ->whereDate('date', '>=', $thisMonth)
            ->count();

        $daysIntoMonth = now()->day;
        $avgDailySpend = $daysIntoMonth > 0 ? round((float) $totalExpenses / $daysIntoMonth, 0) : 0;

        $expenseChange = $lastMonthExpenses > 0
            ? round((((float) $totalExpenses - (float) $lastMonthExpenses) / (float) $lastMonthExpenses) * 100, 1)
            : 0;

        // Yesterday's summary
        $yesterday = now()->subDay();
        $yesterdayExpenses = $user->transactions()
            ->where('type', 'expense')
            ->whereDate('date', $yesterday)
            ->selectRaw('SUM(COALESCE(converted_amount, amount)) as total, COUNT(*) as count')
            ->first();
        $yesterdayIncome = $user->transactions()
            ->where('type', 'income')
            ->whereDate('date', $yesterday)
            ->selectRaw('SUM(COALESCE(converted_amount, amount)) as total')
            ->value('total') ?? 0;

        $unsettledLoans = $user->loans()->whereNull('settled_at')->get();
        $totalLent = (float) $unsettledLoans->where('type', 'lent')->sum(fn ($l) => $l->converted_amount ?? $l->amount);
        $totalBorrowed = (float) $unsettledLoans->where('type', 'borrowed')->sum(fn ($l) => $l->converted_amount ?? $l->amount);

        return Inertia::render('Dashboard', [
            'wallets' => $wallets,
            'recentTransactions' => $recentTransactions,
            'stats' => [
                'totalExpenses' => (float) $totalExpenses,
                'totalIncome' => (float) $totalIncome,
                'balance' => (float) ($totalIncome - $totalExpenses),
                'netWorth' => (float) $netWorth,
                'savingsRate' => $totalIncome > 0 ? round((((float) $totalIncome - (float) $totalExpenses) / (float) $totalIncome) * 100, 1) : 0,
                'avgDailySpend' => $avgDailySpend,
                'txCount' => $txCountThisMonth,
                'expenseChange' => $expenseChange,
            ],
            'categoryBreakdown' => $categoryBreakdown,
            'loans' => [
                'totalLent' => $totalLent,
                'totalBorrowed' => $totalBorrowed,
                'netPosition' => $totalLent - $totalBorrowed,
                'count' => $unsettledLoans->count(),
            ],
            'yesterday' => [
                'expenses' => (float) ($yesterdayExpenses->total ?? 0),
                'income' => (float) $yesterdayIncome,
                'count' => (int) ($yesterdayExpenses->count ?? 0),
                'date' => $yesterday->toDateString(),
            ],
            'entityBreakdown' => Inertia::defer(function () use ($user, $thisMonth) {
                return $user->entities()
                    ->withSum(['transactions as total_spent' => fn ($q) => $q->where('type', 'expense')->whereDate('date', '>=', $thisMonth)], 'converted_amount')
                    ->withCount(['transactions as tx_count' => fn ($q) => $q->whereDate('date', '>=', $thisMonth)])
                    ->get(['id', 'name', 'type', 'color', 'emoji'])
                    ->filter(fn ($e) => ($e->tx_count ?? 0) > 0)
                    ->sortByDesc('total_spent')
                    ->values();
            }),
            'recurring' => Inertia::defer(fn () => RecurringDetectionService::detect($user)),
            'insights' => Inertia::defer(fn () => SpendingInsightService::analyze($user)),
        ]);
    }
}
