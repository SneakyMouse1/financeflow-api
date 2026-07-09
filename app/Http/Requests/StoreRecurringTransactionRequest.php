<?php

namespace App\Http\Requests;

use App\Enums\RecurringFrequency;
use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreRecurringTransactionRequest extends FormRequest
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
            'name'          => ['required', 'string', 'max:255'],
            // Ownership check — prevents using another user's account
            'account_id'    => ['required', 'integer', Rule::exists('accounts', 'id')->where('user_id', $userId)->whereNull('deleted_at')],
            'category_id'   => ['nullable', 'integer', Rule::exists('categories', 'id')->where('user_id', $userId)->whereNull('deleted_at')],
            // Only income/expense — transfers cannot be recurring (no to_account_id logic)
            'type'          => ['required', new Enum(TransactionType::class), Rule::notIn([TransactionType::Transfer->value])],
            'amount'        => ['required', 'numeric', 'min:0.01'],
            'currency_code' => ['required', 'string', 'max:10'],
            'frequency'     => ['required', new Enum(RecurringFrequency::class)],
            'next_run_at'   => ['required', 'date'],
            'comment'       => ['nullable', 'string', 'max:500'],
            'is_active'     => ['sometimes', 'boolean'],
        ];
    }
}
