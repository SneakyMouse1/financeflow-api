<?php

namespace App\Http\Requests;

use App\Enums\BudgetPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreBudgetRequest extends FormRequest
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
            // Only the user's own categories can be used for budgets
            'category_id'   => ['required', 'integer', Rule::exists('categories', 'id')->where('user_id', $this->user()->id)->whereNull('deleted_at')],
            'period'        => ['required', new Enum(BudgetPeriod::class)],
            'amount'        => ['required', 'numeric', 'min:0.01'],
            'currency_code' => ['required', 'string', 'max:10'],
        ];
    }
}
