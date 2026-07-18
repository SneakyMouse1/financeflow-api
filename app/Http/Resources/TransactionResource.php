<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Transaction
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
            // whenLoaded( ) loads the associated data only if it has been eager-loaded using with() in the controller.
            // N + 1 protection
            'account' => new AccountResource($this->whenLoaded('account')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'type' => $this->type->value,
            'amount' => $this->amount,
            'currency_code' => $this->currency_code,
            'date' => $this->date->toDateString(),
            'comment' => $this->comment,
            'transfer_id' => $this->transfer_id,
            'related_transaction' => $this->whenLoaded('relatedTransaction', function () {
                return [
                    'id' => $this->relatedTransaction->id,
                    'account' => new AccountResource($this->relatedTransaction->account),
                ];
            }),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
