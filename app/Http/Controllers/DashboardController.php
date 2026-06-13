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
            ->whereDate('date', '>=', $thisMonth)->selectRaw('currency, SUM(amount) as total')->groupBy('currency')->pluck('total', 'currency')->toArray();

        $totalIncome = $user->transactions()
            ->where('type', 'income')
            ->whereDate('date', '>=', $thisMonth)->selectRaw('currency, SUM(amount) as total')->groupBy('currency')->pluck('total', 'currency')->toArray();

        $lastMonthExpenses = $user->transactions()
            ->where('type', 'expense')
            ->whereDate('date', '>=', $lastMonth)
            ->whereDate('date', '<=', $lastMonthEnd)->selectRaw('currency, SUM(amount) as total')->groupBy('currency')->pluck('total', 'currency')->toArray();

        $netWorth = $user->wallets()->where('is_active', true)->selectRaw('currency, SUM(balance) as total')->groupBy('currency')->pluck('total', 'currency')->toArray();

        $categoryBreakdown = $user->transactions()
            ->with('category:id,name,color')
            ->where('type', 'expense')
            ->whereDate('date', '>=', $thisMonth)
            ->selectRaw('category_id, currency, SUM(amount) as total')->groupBy('category_id', 'currency')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $txCountThisMonth = $user->transactions()
            ->whereDate('date', '>=', $thisMonth)
            ->count();

        $avgDailySpend = [];
        $balance = [];
        $expenseChange = [];
        $savingsRate = [];

        $daysIntoMonth = now()->day;
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

                // Yesterday's summary
        $yesterday = now()->subDay();
        $yesterdayExpenses = $user->transactions()
            ->where('type', 'expense')
            ->whereDate('date', $yesterday)->selectRaw('currency, SUM(amount) as total, COUNT(*) as count')->groupBy('currency')->get();
        $yesterdayIncome = $user->transactions()
            ->where('type', 'income')
            ->whereDate('date', $yesterday)->selectRaw('currency, SUM(amount) as total')->groupBy('currency')->pluck('total', 'currency')->toArray();

        $yesterdaySummary = [];
        $yCurrencies = $yesterdayExpenses->pluck('currency')->merge(array_keys($yesterdayIncome))->unique();
        foreach ($yCurrencies as $c) {
            $yExp = $yesterdayExpenses->firstWhere('currency', $c);
            $yesterdaySummary[$c] = [
                'expenses' => (float) ($yExp->total ?? 0),
                'count' => (int) ($yExp->count ?? 0),
                'income' => (float) ($yesterdayIncome[$c] ?? 0),
            ];
        }

                $unsettledLoans = $user->loans()->whereNull('settled_at')->get();
        $totalLent = $unsettledLoans->where('type', 'lent')->groupBy('currency')->map->sum('amount')->toArray();
        $totalBorrowed = $unsettledLoans->where('type', 'borrowed')->groupBy('currency')->map->sum('amount')->toArray();

        $netPosition = [];
        $loanCurrencies = collect(array_keys($totalLent))->merge(array_keys($totalBorrowed))->unique();
        foreach ($loanCurrencies as $c) {
            $netPosition[$c] = ($totalLent[$c] ?? 0) - ($totalBorrowed[$c] ?? 0);
        }

        
        

        





        return Inertia::render('Dashboard', [
            'wallets' => $wallets,
            'recentTransactions' => $recentTransactions,
            'stats' => [
                'totalExpenses' => $totalExpenses,
                'totalIncome' => $totalIncome,
                'balance' => $balance,
                'netWorth' => $netWorth,
                'savingsRate' => $savingsRate,
                'avgDailySpend' => $avgDailySpend,
                'txCount' => $txCountThisMonth,
                'expenseChange' => $expenseChange,
            ],
            'categoryBreakdown' => $categoryBreakdown,
            'loans' => [
                'totalLent' => $totalLent,
                'totalBorrowed' => $totalBorrowed,
                'netPosition' => $netPosition,
                'count' => $unsettledLoans->count(),
            ],
            'yesterday' => [
                'summary' => $yesterdaySummary,
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
