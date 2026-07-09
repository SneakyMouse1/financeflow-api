<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreGoalDepositRequest extends FormRequest
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
        return [
            'amount'     => ['required', 'numeric', 'min:0.01'],
            'comment'    => ['nullable', 'string', 'max:500'],
            'account_id' => [
                'sometimes',
                'integer',
                \Illuminate\Validation\Rule::exists('accounts', 'id')
                    ->where('user_id', $this->user()->id)
                    ->whereNull('deleted_at')
            ],
        ];
    }
}
