<?php

namespace App\Http\Requests;

use App\Enums\RecurringFrequency;
use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateRecurringTransactionRequest extends FormRequest
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
            'name'          => ['sometimes', 'string', 'max:255'],
            'account_id'    => ['sometimes', 'integer', Rule::exists('accounts', 'id')->where('user_id', $userId)->whereNull('deleted_at')],
            'category_id'   => ['sometimes', 'nullable', 'integer', Rule::exists('categories', 'id')->where('user_id', $userId)->whereNull('deleted_at')],
            'type'          => ['sometimes', new Enum(TransactionType::class), Rule::notIn([TransactionType::Transfer->value])],
            'amount'        => ['sometimes', 'numeric', 'min:0.01'],
            'currency_code' => ['sometimes', 'string', 'max:10'],
            'frequency'     => ['sometimes', new Enum(RecurringFrequency::class)],
            'next_run_at'   => ['sometimes', 'date'],
            'comment'       => ['sometimes', 'nullable', 'string', 'max:500'],
            'is_active'     => ['sometimes', 'boolean'],
        ];
    }
}
