<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        /*
         * 'sometimes' — validate this field only if it is present in the request.
         * Used in PATCH requests to allow partial updates.
         * Example: user wants to update only the account name:
         * PATCH /api/v1/accounts/1
         * { "name": "My new card name" }
         * With 'required' — Laravel would throw a validation error for missing 'currency_code', 'balance', etc.
         * With 'sometimes' — Laravel only validates 'name', silently skips the rest.
         * Rule of thumb:
         * StoreRequest  → 'required' (all fields needed to create)
         * UpdateRequest → 'sometimes' (only sent fields are validated)
         */

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', new Enum(AccountType::class)],
            'currency_code' => ['sometimes', 'string', 'max:10'],
            'balance' => ['sometimes', 'numeric'],
            'color' => ['nullable', 'string', 'max:7'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_archived' => ['sometimes', 'boolean'],
        ];
    }
}
