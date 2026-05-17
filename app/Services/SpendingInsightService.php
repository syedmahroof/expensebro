<?php

namespace App\Services;

use App\Models\User;

class SpendingInsightService
{
    public static function analyze(User $user): array
    {
        $since = now()->subDays(30);

        $expenses = $user->transactions()
            ->where('type', 'expense')
            ->whereDate('date', '>=', $since)
            ->orderBy('date')
            ->get(['id', 'amount', 'converted_amount', 'date', 'created_at', 'category_id', 'description']);

        if ($expenses->isEmpty()) {
            return [];
        }

        $insights = [];

        // Weekend vs weekday spending
        $weekend = $expenses->filter(fn ($t) => in_array(date('N', strtotime($t->date)), [6, 7]));
        $weekday = $expenses->filter(fn ($t) => !in_array(date('N', strtotime($t->date)), [6, 7]));

        $weekendAvgDay = $weekend->count() > 0
            ? $weekend->sum(fn ($t) => (float) ($t->converted_amount ?? $t->amount)) / max(1, $weekend->groupBy(fn ($t) => $t->date)->count())
            : 0;
        $weekdayAvgDay = $weekday->count() > 0
            ? $weekday->sum(fn ($t) => (float) ($t->converted_amount ?? $t->amount)) / max(1, $weekday->groupBy(fn ($t) => $t->date)->count())
            : 0;

        if ($weekdayAvgDay > 0 && $weekendAvgDay > $weekdayAvgDay * 1.3) {
            $pct = round((($weekendAvgDay - $weekdayAvgDay) / $weekdayAvgDay) * 100);
            $insights[] = [
                'type' => 'weekend_spike',
                'icon' => 'calendar',
                'title' => "Weekend Spending +{$pct}%",
                'description' => 'You spend significantly more on weekends. Consider setting a weekend budget.',
                'severity' => $pct > 60 ? 'high' : 'medium',
                'value' => $weekendAvgDay,
            ];
        }

        // Top spending day of week
        $byDay = $expenses->groupBy(fn ($t) => date('l', strtotime($t->date)));
        $dayTotals = $byDay->map(fn ($g) => $g->sum(fn ($t) => (float) ($t->converted_amount ?? $t->amount)));
        if ($dayTotals->isNotEmpty()) {
            $topDay = $dayTotals->sortDesc()->keys()->first();
            $insights[] = [
                'type' => 'top_day',
                'icon' => 'trending-up',
                'title' => "{$topDay}s Are Your Biggest Spend Day",
                'description' => 'Your transactions cluster on this day — try spreading purchases throughout the week.',
                'severity' => 'info',
                'value' => $dayTotals->max(),
            ];
        }

        // Month-end splurge (last 5 days of month vs rest)
        $monthEndSpend = $expenses->filter(fn ($t) => (int) date('j', strtotime($t->date)) >= 26)
            ->sum(fn ($t) => (float) ($t->converted_amount ?? $t->amount));
        $monthMidSpend = $expenses->filter(fn ($t) => (int) date('j', strtotime($t->date)) < 26)
            ->sum(fn ($t) => (float) ($t->converted_amount ?? $t->amount));
        $monthMidDays = max(1, $expenses->filter(fn ($t) => (int) date('j', strtotime($t->date)) < 26)->groupBy(fn ($t) => $t->date)->count());
        $monthEndDays = max(1, $expenses->filter(fn ($t) => (int) date('j', strtotime($t->date)) >= 26)->groupBy(fn ($t) => $t->date)->count());

        if ($monthEndDays > 0 && $monthMidDays > 0) {
            $midAvg = $monthMidSpend / $monthMidDays;
            $endAvg = $monthEndSpend / $monthEndDays;

            if ($endAvg > $midAvg * 1.4) {
                $insights[] = [
                    'type' => 'month_end',
                    'icon' => 'alert-triangle',
                    'title' => 'Month-End Spending Spike',
                    'description' => 'You tend to spend more at month-end. Review your purchases before payday arrives.',
                    'severity' => 'medium',
                    'value' => $endAvg,
                ];
            }
        }

        // Single large transaction (impulse)
        $totalAvg = $expenses->average(fn ($t) => (float) ($t->converted_amount ?? $t->amount));
        $large = $expenses->filter(fn ($t) => (float) ($t->converted_amount ?? $t->amount) > $totalAvg * 4)->sortByDesc(fn ($t) => $t->amount)->first();

        if ($large) {
            $insights[] = [
                'type' => 'large_purchase',
                'icon' => 'zap',
                'title' => 'Large One-Time Purchase Detected',
                'description' => "A transaction of " . number_format((float) ($large->converted_amount ?? $large->amount), 0) . " is 4× your average expense — was this planned?",
                'severity' => 'info',
                'value' => (float) ($large->converted_amount ?? $large->amount),
            ];
        }

        // Savings encouragement
        $income = $user->transactions()
            ->where('type', 'income')
            ->whereDate('date', '>=', $since)
            ->sum('amount');

        $totalExpense = $expenses->sum(fn ($t) => (float) ($t->converted_amount ?? $t->amount));

        if ($income > 0 && $totalExpense < $income * 0.5) {
            $insights[] = [
                'type' => 'great_saver',
                'icon' => 'piggy-bank',
                'title' => "You're Saving Over 50% This Month!",
                'description' => 'Excellent financial discipline. Consider investing the surplus.',
                'severity' => 'positive',
                'value' => $income - $totalExpense,
            ];
        }

        return array_slice($insights, 0, 5);
    }
}
