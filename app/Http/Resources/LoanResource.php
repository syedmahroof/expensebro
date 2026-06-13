<?php

namespace App\Http\Resources;

use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Loan
 */
class LoanResource extends JsonResource
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
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'converted_amount' => $this->converted_amount !== null ? (float) $this->converted_amount : null,
            'description' => $this->description,
            'due_date' => $this->due_date?->toDateString(),
            'settled_at' => $this->settled_at?->toIso8601String(),
            'is_settled' => $this->isSettled(),
            'is_overdue' => $this->isOverdue(),
            'notes' => $this->notes,
        ];
    }
}
