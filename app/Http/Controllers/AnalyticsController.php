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
                ->selectRaw('SUM(COALESCE(converted_amount, amount)) as total')
                ->value('total') ?? 0;

            $income = $user->transactions()
                ->where('type', 'income')
                ->whereYear('date', $month->year)
                ->whereMonth('date', $month->month)
                ->selectRaw('SUM(COALESCE(converted_amount, amount)) as total')
                ->value('total') ?? 0;

            return [
                'month' => $month->format('M'),
                'year' => $month->year,
                'expenses' => (float) $expenses,
                'income' => (float) $income,
            ];
        })->values();

        $categoryBreakdown = $user->transactions()
            ->with('category:id,name,color')
            ->where('type', 'expense')
            ->whereDate('date', '>=', now()->startOfMonth())
            ->selectRaw('category_id, SUM(COALESCE(converted_amount, amount)) as total, COUNT(*) as count')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        $walletBreakdown = $user->wallets()
            ->where('is_active', true)
            ->get(['id', 'name', 'balance', 'currency', 'color', 'type']);

        $topMerchants = $user->transactions()
            ->with('merchant:id,name')
            ->whereNotNull('merchant_id')
            ->where('type', 'expense')
            ->selectRaw('merchant_id, SUM(COALESCE(converted_amount, amount)) as total, COUNT(*) as count')
            ->groupBy('merchant_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $totalExpensesThisMonth = $user->transactions()
            ->where('type', 'expense')
            ->whereDate('date', '>=', now()->startOfMonth())
            ->selectRaw('SUM(COALESCE(converted_amount, amount)) as total')
            ->value('total') ?? 0;

        $totalExpensesLastMonth = $user->transactions()
            ->where('type', 'expense')
            ->whereDate('date', '>=', now()->subMonth()->startOfMonth())
            ->whereDate('date', '<=', now()->subMonth()->endOfMonth())
            ->selectRaw('SUM(COALESCE(converted_amount, amount)) as total')
            ->value('total') ?? 0;

        return Inertia::render('Analytics', [
            'monthlyTrend' => $monthlyTrend,
            'categoryBreakdown' => $categoryBreakdown,
            'walletBreakdown' => $walletBreakdown,
            'topMerchants' => $topMerchants,
            'stats' => [
                'thisMonth' => (float) $totalExpensesThisMonth,
                'lastMonth' => (float) $totalExpensesLastMonth,
                'change' => $totalExpensesLastMonth > 0
                    ? round((($totalExpensesThisMonth - $totalExpensesLastMonth) / $totalExpensesLastMonth) * 100, 1)
                    : 0,
                'totalTransactions' => $user->transactions()->count(),
            ],
        ]);
    }
}
