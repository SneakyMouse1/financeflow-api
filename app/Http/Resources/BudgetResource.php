<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Budget
 * @property float|null $spent
 */
class BudgetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $this->spent is set by BudgetService via setAttribute() — no SQL here.
        $spent     = (float) ($this->spent ?? 0);
        $amount    = (float) $this->amount;
        $remaining = max(0, $amount - $spent);

        return [
            'id'                  => $this->id,
            'category'            => new CategoryResource($this->whenLoaded('category')),
            'period'              => $this->period->value,
            'amount'              => $amount,
            'currency_code'       => $this->currency_code,
            'spent'               => $spent,
            'remaining'           => $remaining,
            'progress_percentage' => $amount > 0
                ? round(($spent / $amount) * 100, 2)
                : 0,
            'created_at'          => $this->created_at->toDateTimeString(),
        ];
    }
}
