<?php

namespace App\Models;

use Database\Factories\GroupExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'group_id', 'paid_by_member_id', 'amount', 'currency',
    'converted_amount', 'description', 'date', 'notes',
])]
class GroupExpense extends Model
{
    /** @use HasFactory<GroupExpenseFactory> */
    use HasFactory;

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function paidByMember(): BelongsTo
    {
        return $this->belongsTo(GroupMember::class, 'paid_by_member_id');
    }

    public function splits(): HasMany
    {
        return $this->hasMany(GroupExpenseSplit::class);
    }

    public function effectiveAmount(): float
    {
        return (float) ($this->converted_amount ?? $this->amount);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'converted_amount' => 'decimal:2',
            'date' => 'date',
        ];
    }
}
