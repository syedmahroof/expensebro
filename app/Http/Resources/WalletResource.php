<?php

namespace App\Http\Resources;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Wallet
 */
class WalletResource extends JsonResource
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
            'name' => $this->name,
            'type' => $this->type,
            'currency' => $this->currency,
            'balance' => (float) $this->balance,
            'color' => $this->color,
            'icon' => $this->icon,
            'is_default' => (bool) $this->is_default,
            'is_active' => (bool) $this->is_active,
            'transactions_count' => $this->whenCounted('transactions'),
        ];
    }
}
