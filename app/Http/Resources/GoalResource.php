<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Goal
 */
class GoalResource extends JsonResource
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
            'target_amount' => $this->target_amount,
            'current_amount' => $this->current_amount,
            'currency_code' => $this->currency_code,
            'status' => $this->status->value,
            'deadline' => $this->deadline?->toDateString(),
            // The data is already in the model. Division by zero protection
            'progress_percentage' => $this->target_amount > 0
                ? round(($this->current_amount / $this->target_amount) * 100, 2)
                : 0,
            'deposits' => GoalDepositResource::collection($this->whenLoaded('deposits')),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
