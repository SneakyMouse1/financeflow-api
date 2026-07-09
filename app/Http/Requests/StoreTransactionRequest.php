<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreTransactionRequest extends FormRequest
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
            // Rule::exists()->where('user_id') ensures the account belongs to the authenticated user.
            // Plain 'exists:accounts,id' would allow using another user's account_id.
            'account_id'    => ['required', 'integer', Rule::exists('accounts', 'id')->where('user_id', $userId)->whereNull('deleted_at')],
            'category_id'   => ['nullable', 'integer', Rule::exists('categories', 'id')->where('user_id', $userId)->whereNull('deleted_at')],
            'type'          => ['required', new Enum(TransactionType::class)],
            'amount'        => ['required', 'numeric', 'min:0.01'],
            'currency_code' => ['required', 'string', 'max:10'],
            'date'          => ['required', 'date'],
            'comment'       => ['nullable', 'string', 'max:500'],
            // Takes an array of tag IDs ([1, 3, 5])
            'tags'          => ['nullable', 'array'],
            // Validation rule for each element in the array, which checks that each ID exists in the 'tags' table
            'tags.*'        => ['integer', Rule::exists('tags', 'id')->where('user_id', $userId)],
            // Field is required only if the transaction type is 'transfer',
            // and 'different:account_id' ensures that funds cannot be transferred to the same account.
            'to_account_id' => ['required_if:type,transfer', 'integer', Rule::exists('accounts', 'id')->where('user_id', $userId)->whereNull('deleted_at'), 'different:account_id'],
        ];
    }
}
