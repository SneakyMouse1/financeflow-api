<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\RecurringTransaction
 */
class RecurringTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            // whenLoaded() — N+1 protection, loaded only when eager-loaded via with()
            'account'       => new AccountResource($this->whenLoaded('account')),
            'category'      => new CategoryResource($this->whenLoaded('category')),
            'type'          => $this->type->value,
            'amount'        => $this->amount,
            'currency_code' => $this->currency_code,
            'frequency'     => $this->frequency->value,
            'next_run_at'   => $this->next_run_at->toDateString(),
            'comment'       => $this->comment,
            'is_active'     => $this->is_active,
            'created_at'    => $this->created_at->toDateTimeString(),
            'updated_at'    => $this->updated_at->toDateTimeString(),
        ];
    }
}
