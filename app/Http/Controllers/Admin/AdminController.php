<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function __invoke(): Response
    {
        $totalUsers = User::count();
        $newUsersThisMonth = User::whereDate('created_at', '>=', now()->startOfMonth())->count();
        $totalTransactions = Transaction::count();
        $totalVolume = Transaction::where('type', 'expense')->sum('amount');
        $aiParsedCount = Transaction::where('ai_parsed', true)->count();

        $recentUsers = User::latest()
            ->limit(10)
            ->get(['id', 'name', 'email', 'created_at', 'social_provider', 'phone', 'is_admin']);

        $monthlySignups = collect(range(5, 0))->map(fn ($m) => [
            'month' => now()->subMonths($m)->format('M'),
            'count' => User::whereYear('created_at', now()->subMonths($m)->year)
                ->whereMonth('created_at', now()->subMonths($m)->month)
                ->count(),
        ])->values();

        $monthlyVolume = collect(range(5, 0))->map(fn ($m) => [
            'month' => now()->subMonths($m)->format('M'),
            'amount' => (float) Transaction::where('type', 'expense')
                ->whereYear('date', now()->subMonths($m)->year)
                ->whereMonth('date', now()->subMonths($m)->month)
                ->sum('amount'),
        ])->values();

        return Inertia::render('Admin/Index', [
            'stats' => [
                'totalUsers' => $totalUsers,
                'newUsersThisMonth' => $newUsersThisMonth,
                'totalTransactions' => $totalTransactions,
                'totalVolume' => (float) $totalVolume,
                'aiParsedCount' => $aiParsedCount,
                'aiParsedPercent' => $totalTransactions > 0
                    ? round(($aiParsedCount / $totalTransactions) * 100)
                    : 0,
            ],
            'recentUsers' => $recentUsers,
            'monthlySignups' => $monthlySignups,
            'monthlyVolume' => $monthlyVolume,
        ]);
    }
}
