<?php

namespace App\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'trip_id', 'wallet_id', 'category_id', 'merchant_id', 'receipt_id', 'entity_id',
    'type', 'amount', 'converted_amount', 'converted_currency', 'exchange_rate',
    'currency', 'description', 'notes', 'date', 'location',
    'ai_parsed', 'ai_raw', 'is_recurring',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'converted_amount' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'date' => 'date',
            'ai_parsed' => 'boolean',
            'ai_raw' => 'array',
            'is_recurring' => 'boolean',
        ];
    }

    public function effectiveAmount(): float
    {
        return (float) ($this->converted_amount ?? $this->amount);
    }
}
