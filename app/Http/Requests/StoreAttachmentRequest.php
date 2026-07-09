<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttachmentRequest extends FormRequest
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
            // Ensure the transaction belongs to the authenticated user
            'transaction_id' => [
                'required',
                'integer',
                Rule::exists('transactions', 'id')
                    ->where('user_id', $this->user()->id)
                    ->whereNull('deleted_at'),
            ],
            // 5MB files
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:5120'],
        ];
    }
}
