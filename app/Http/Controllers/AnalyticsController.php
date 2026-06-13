<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = Auth::user();

        $months = collect(range(5, 0))->map(fn ($m) => now()->subMonths($m));

        $monthlyTrend = $months->map(function ($month) use ($user) {
            $expenses = $user->transactions()
                ->where('type', 'expense')
                ->whereYear('date', $month->year)
                ->whereMonth('date', $month->month)
                ->selectRaw('currency, SUM(amount) as total')
                ->groupBy('currency')
                ->pluck('total', 'currency')
                ->toArray();

            $income = $user->transactions()
                ->where('type', 'income')
                ->whereYear('date', $month->year)
                ->whereMonth('date', $month->month)
                ->selectRaw('currency, SUM(amount) as total')
                ->groupBy('currency')
                ->pluck('total', 'currency')
                ->toArray();

            return [
                'month' => $month->format('M'),
                'year' => $month->year,
                'expenses' => $expenses, 'income' => $income,
            ];
        })->values();

        $categoryBreakdown = $user->transactions()
            ->with('category:id,name,color')
            ->where('type', 'expense')
            ->whereDate('date', '>=', now()->startOfMonth())
            ->selectRaw('category_id, currency, SUM(amount) as total, COUNT(*) as count')->groupBy('category_id', 'currency')
            ->orderByDesc('total')
            ->get();

        $walletBreakdown = $user->wallets()
            ->where('is_active', true)
            ->get(['id', 'name', 'balance', 'currency', 'color', 'type']);

        $topMerchants = $user->transactions()
            ->with('merchant:id,name')
            ->whereNotNull('merchant_id')
            ->where('type', 'expense')
            ->selectRaw('merchant_id, currency, SUM(amount) as total, COUNT(*) as count')->groupBy('merchant_id', 'currency')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $totalExpensesThisMonth = $user->transactions()
            ->where('type', 'expense')
            ->whereDate('date', '>=', now()->startOfMonth())
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        $totalExpensesLastMonth = $user->transactions()
            ->where('type', 'expense')
            ->whereDate('date', '>=', now()->subMonth()->startOfMonth())
            ->whereDate('date', '<=', now()->subMonth()->endOfMonth())
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        $change = [];
        $allCurrencies = collect(array_keys($totalExpensesThisMonth))->merge(array_keys($totalExpensesLastMonth))->unique();
        foreach ($allCurrencies as $c) {
            $thisM = $totalExpensesThisMonth[$c] ?? 0;
            $lastM = $totalExpensesLastMonth[$c] ?? 0;
            $change[$c] = $lastM > 0 ? round((($thisM - $lastM) / $lastM) * 100, 1) : 0;
        }

        return Inertia::render('Analytics', [
            'monthlyTrend' => $monthlyTrend,
            'categoryBreakdown' => $categoryBreakdown,
            'walletBreakdown' => $walletBreakdown,
            'topMerchants' => $topMerchants,
            'stats' => [
                'thisMonth' => $totalExpensesThisMonth,
                'lastMonth' => $totalExpensesLastMonth,
                'change' => $change,
                'totalTransactions' => $user->transactions()->count(),
            ],
        ]);
    }
}
