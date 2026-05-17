<?php

namespace App\Models;

use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['user_id', 'name', 'description', 'currency'])]
class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(GroupExpense::class);
    }

    public function splits(): HasManyThrough
    {
        return $this->hasManyThrough(GroupExpenseSplit::class, GroupExpense::class);
    }

    public function totalExpenses(): float
    {
        return (float) $this->expenses()->sum('converted_amount');
    }

    /** @return array<int, array{from: int, to: int, fromName: string, toName: string, amount: float}> */
    public function balances(): array
    {
        $members = $this->members()->pluck('name', 'id');
        $net = array_fill_keys($members->keys()->toArray(), 0.0);

        foreach ($this->expenses()->with(['splits', 'paidByMember'])->get() as $expense) {
            $paidBy = $expense->paid_by_member_id;

            foreach ($expense->splits as $split) {
                $share = (float) $split->amount;
                if ($split->group_member_id === $paidBy || $split->settled_at) {
                    continue;
                }
                $net[$paidBy] = ($net[$paidBy] ?? 0) + $share;
                $net[$split->group_member_id] = ($net[$split->group_member_id] ?? 0) - $share;
            }
        }

        return $this->minimizeTransactions($net, $members->toArray());
    }

    /** @param array<int, float> $net @param array<int, string> $names */
    private function minimizeTransactions(array $net, array $names): array
    {
        $creditors = [];
        $debtors = [];
        foreach ($net as $id => $amount) {
            if ($amount > 0.005) {
                $creditors[] = ['id' => $id, 'amount' => $amount];
            } elseif ($amount < -0.005) {
                $debtors[] = ['id' => $id, 'amount' => -$amount];
            }
        }

        $transactions = [];
        $i = 0;
        $j = 0;
        while ($i < count($creditors) && $j < count($debtors)) {
            $transfer = min($creditors[$i]['amount'], $debtors[$j]['amount']);
            $transactions[] = [
                'from' => $debtors[$j]['id'],
                'to' => $creditors[$i]['id'],
                'fromName' => $names[$debtors[$j]['id']] ?? '',
                'toName' => $names[$creditors[$i]['id']] ?? '',
                'amount' => round($transfer, 2),
            ];
            $creditors[$i]['amount'] -= $transfer;
            $debtors[$j]['amount'] -= $transfer;
            if ($creditors[$i]['amount'] < 0.005) {
                $i++;
            }
            if ($debtors[$j]['amount'] < 0.005) {
                $j++;
            }
        }

        return $transactions;
    }
}
