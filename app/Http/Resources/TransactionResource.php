<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'converted_amount' => $this->converted_amount !== null ? (float) $this->converted_amount : null,
            'converted_currency' => $this->converted_currency,
            'description' => $this->description,
            'notes' => $this->notes,
            'date' => $this->date?->toDateString(),
            'location' => $this->location,
            'created_at' => $this->created_at?->toIso8601String(),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'wallet' => $this->whenLoaded('wallet', fn () => [
                'id' => $this->wallet->id,
                'name' => $this->wallet->name,
                'currency' => $this->wallet->currency,
            ]),
            'merchant' => $this->whenLoaded('merchant', fn () => $this->merchant ? [
                'id' => $this->merchant->id,
                'name' => $this->merchant->name,
            ] : null),
        ];
    }
}
