<?php

namespace App\Models;

use Database\Factories\GroupExpenseSplitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['group_expense_id', 'group_member_id', 'amount', 'settled_at'])]
class GroupExpenseSplit extends Model
{
    /** @use HasFactory<GroupExpenseSplitFactory> */
    use HasFactory;

    public function expense(): BelongsTo
    {
        return $this->belongsTo(GroupExpense::class, 'group_expense_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(GroupMember::class, 'group_member_id');
    }

    public function isSettled(): bool
    {
        return $this->settled_at !== null;
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'settled_at' => 'datetime',
        ];
    }
}
