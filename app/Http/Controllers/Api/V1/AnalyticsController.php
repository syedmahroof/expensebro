<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Spending analytics: 6-month trend, category split, wallet split, top merchants.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $currency = $user->default_currency ?? 'PKR';

        $monthlyTrend = collect(range(5, 0))->map(function ($m) use ($user) {
            $month = now()->subMonths($m);

            $sum = fn (string $type) => $user->transactions()
                ->where('type', $type)
                ->whereYear('date', $month->year)
                ->whereMonth('date', $month->month)
                ->selectRaw('currency, SUM(amount) as total')
                ->groupBy('currency')
                ->pluck('total', 'currency')
                ->toArray();

            return [
                'month' => $month->format('M'),
                'year' => $month->year,
                'expenses' => $sum('expense'),
                'income' => $sum('income'),
            ];
        })->values();

        $categoryBreakdown = $user->transactions()
            ->with('category:id,name,color')
            ->where('type', 'expense')
            ->whereDate('date', '>=', now()->startOfMonth())
            ->selectRaw('category_id, currency, SUM(amount) as total, COUNT(*) as count')->groupBy('category_id', 'currency')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->category?->name ?? 'Uncategorized',
                'color' => $row->category?->color ?? '#6b7280',
                'currency' => $row->currency, 'total' => (float) $row->total,
                'count' => (int) $row->count,
            ]);

        $topMerchants = $user->transactions()
            ->with('merchant:id,name')
            ->whereNotNull('merchant_id')
            ->where('type', 'expense')
            ->selectRaw('merchant_id, currency, SUM(amount) as total, COUNT(*) as count')->groupBy('merchant_id', 'currency')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->merchant?->name ?? 'Unknown',
                'currency' => $row->currency, 'total' => (float) $row->total,
                'count' => (int) $row->count,
            ]);

        $thisMonth = $user->transactions()
            ->where('type', 'expense')
            ->whereDate('date', '>=', now()->startOfMonth())
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        $lastMonth = $user->transactions()
            ->where('type', 'expense')
            ->whereDate('date', '>=', now()->subMonth()->startOfMonth())
            ->whereDate('date', '<=', now()->subMonth()->endOfMonth())
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();

        $change = [];
        $allCurrencies = collect(array_keys($thisMonth))->merge(array_keys($lastMonth))->unique();
        foreach ($allCurrencies as $c) {
            $t = $thisMonth[$c] ?? 0;
            $l = $lastMonth[$c] ?? 0;
            $change[$c] = $l > 0 ? round((($t - $l) / $l) * 100, 1) : 0;
        }

        return response()->json([
            'currency' => $currency,
            'monthly_trend' => $monthlyTrend,
            'category_breakdown' => $categoryBreakdown,
            'wallet_breakdown' => WalletResource::collection(
                $user->wallets()->where('is_active', true)->get()
            ),
            'top_merchants' => $topMerchants,
            'stats' => [
                'this_month' => $thisMonth,
                'last_month' => $lastMonth,
                'change' => $change,
                'total_transactions' => $user->transactions()->count(),
            ],
        ]);
    }
}
