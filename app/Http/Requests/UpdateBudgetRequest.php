<?php

namespace App\Http\Requests;

use App\Enums\BudgetPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateBudgetRequest extends FormRequest
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
            // Make sure the category belongs to the authenticated user to prevent data isolation violations (using Rule::exists)
            'category_id' => ['sometimes', 'integer', Rule::exists('categories', 'id')->where('user_id', $this->user()->id)->whereNull('deleted_at')],
            'period' => ['sometimes', new Enum(BudgetPeriod::class)],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'currency_code' => ['sometimes', 'string', 'max:10'],
        ];
    }
}
