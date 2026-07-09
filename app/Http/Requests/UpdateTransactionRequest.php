<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateTransactionRequest extends FormRequest
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
        $userId = $this->user()->id;

        return [
            'account_id'    => ['sometimes', 'integer', Rule::exists('accounts', 'id')->where('user_id', $userId)->whereNull('deleted_at')],
            'category_id'   => ['nullable', 'integer', Rule::exists('categories', 'id')->where('user_id', $userId)->whereNull('deleted_at')],
            // Changing type to 'transfer' via update is forbidden — transfers
            // are atomic pairs that must be created through the dedicated flow.
            'type'          => ['sometimes', new Enum(TransactionType::class), Rule::notIn([TransactionType::Transfer->value])],
            'amount'        => ['sometimes', 'numeric', 'min:0.01'],
            'currency_code' => ['sometimes', 'string', 'max:10'],
            'date'          => ['sometimes', 'date'],
            'comment'       => ['nullable', 'string', 'max:500'],
            'tags'          => ['nullable', 'array'],
            'tags.*'        => ['integer', Rule::exists('tags', 'id')->where('user_id', $userId)],
        ];
    }
}
