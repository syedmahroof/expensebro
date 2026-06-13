<?php

namespace App\Http\Resources;

use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Entity
 */
class EntityResource extends JsonResource
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
            'color' => $this->color,
            'emoji' => $this->emoji,
            'description' => $this->description,
            'transactions_count' => $this->whenCounted('transactions'),
            'total_spent' => $this->when(
                array_key_exists('total_spent', $this->getAttributes()),
                fn () => (float) $this->total_spent,
            ),
        ];
    }
}
