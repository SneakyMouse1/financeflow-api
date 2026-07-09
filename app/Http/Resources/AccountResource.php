<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Account
 */
class AccountResource extends JsonResource
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
            'type' => $this->type->value,
            'currency_code' => $this->currency_code,

            'balance' => (float) $this->balance,
            'color' => $this->color,
            'icon' => $this->icon,
            'is_archived' => $this->is_archived,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
