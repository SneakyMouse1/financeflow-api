<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\GoalDeposit
 */
class GoalDepositResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'transaction_id' => $this->transaction_id,
            'amount'         => $this->amount,
            'comment'        => $this->comment,
            'created_at'     => $this->created_at->toDateTimeString(),
        ];
    }
}
