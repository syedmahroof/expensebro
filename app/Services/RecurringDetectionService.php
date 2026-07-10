<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class RecurringDetectionService
{
    public static function detect(User $user): array
    {
        $since = now()->subMonths(4);

        $transactions = $user->transactions()
            ->with(['category:id,name,color', 'merchant:id,name'])
            ->where('type', '!=', 'transfer')
            ->whereDate('date', '>=', $since)
            ->orderBy('date')
            ->get(['id', 'description', 'amount', 'currency', 'converted_amount', 'type', 'date', 'merchant_id', 'category_id']);

        $groups = $transactions->groupBy(function ($tx) {
            return $tx->merchant_id
                ? 'merchant_'.$tx->merchant_id
                : strtolower(trim($tx->description ?? ''));
        })->filter(fn ($g) => $g->count() >= 2);

        $recurring = [];

        foreach ($groups as $key => $group) {
            $sorted = $group->sortBy('date')->values();
            $intervals = [];

            for ($i = 1; $i < $sorted->count(); $i++) {
                $intervals[] = Carbon::parse($sorted[$i - 1]->date)->diffInDays(Carbon::parse($sorted[$i]->date));
            }

            if (empty($intervals)) {
                continue;
            }

            $avgInterval = array_sum($intervals) / count($intervals);

            if ($avgInterval >= 25 && $avgInterval <= 35) {
                $label = 'Monthly';
            } elseif ($avgInterval >= 6 && $avgInterval <= 8) {
                $label = 'Weekly';
            } elseif ($avgInterval >= 13 && $avgInterval <= 16) {
                $label = 'Bi-weekly';
            } else {
                continue;
            }

            $last = $sorted->last();
            $avgAmount = $sorted->average(fn ($t) => (float) ($t->converted_amount ?? $t->amount));
            $nextDate = Carbon::parse($last->date)->addDays((int) round($avgInterval));

            $recurring[] = [
                'key' => $key,
                'name' => $last->merchant?->name ?? ucfirst($last->description ?? 'Unknown'),
                'type' => $last->type,
                'interval' => $label,
                'avg_amount' => round($avgAmount, 0),
                'currency' => $last->currency,
                'last_date' => $last->date,
                'next_date' => $nextDate->toDateString(),
                'is_overdue' => $nextDate->isPast(),
                'days_until' => (int) now()->diffInDays($nextDate, false),
                'category' => $last->category ? ['name' => $last->category->name, 'color' => $last->category->color] : null,
                'occurrences' => $sorted->count(),
            ];
        }

        usort($recurring, fn ($a, $b) => $a['days_until'] <=> $b['days_until']);

        return array_slice($recurring, 0, 8);
    }
}
