<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Explicitly whitelist fields — never expose password, remember_token, etc.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'avatar'            => $this->avatar,
            'settings'          => $this->settings,
            'email_verified_at' => $this->email_verified_at?->toDateTimeString(),
            'created_at'        => $this->created_at->toDateTimeString(),
        ];
    }
}
