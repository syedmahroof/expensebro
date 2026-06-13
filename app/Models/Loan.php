<?php

namespace App\Models;

use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'contact_name', 'contact_phone', 'type',
    'amount', 'currency', 'converted_amount',
    'description', 'due_date', 'settled_at', 'notes',
])]
class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSettled(): bool
    {
        return $this->settled_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isSettled()
            && $this->due_date !== null
            && $this->due_date->isPast();
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'converted_amount' => 'decimal:2',
            'due_date' => 'date',
            'settled_at' => 'datetime',
        ];
    }
}
