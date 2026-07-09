<?php

namespace App\Http\Requests;

use App\Enums\GoalStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateGoalRequest extends FormRequest
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
            'name'          => ['sometimes', 'string', 'max:255'],
            'target_amount' => ['sometimes', 'numeric', 'min:0.01'],
            'currency_code' => ['sometimes', 'string', 'max:10'],
            'status'        => ['sometimes', new Enum(GoalStatus::class)],
            'deadline'      => ['sometimes', 'nullable', 'date', 'after:today'],
        ];
    }

    /**
     * Get the validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_amount.min' => 'Target amount must be greater than zero.',
        ];
    }

    /**
     * After-validation hook: ensure target_amount cannot be set below current_amount.
     */
    protected function passedValidation(): void
    {
        $goal = $this->route('goal');

        if ($this->has('target_amount') && $goal && $this->target_amount < $goal->current_amount) {
            $this->validator->errors()->add(
                'target_amount',
                'Target amount cannot be less than the current saved amount (' . $goal->current_amount . ').'
            );

            $this->failedValidation($this->validator);
        }
    }
}
