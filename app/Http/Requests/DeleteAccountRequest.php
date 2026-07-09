<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
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
            // 'current_password' is a built-in Laravel rule that checks the value
            // against the authenticated user's hashed password.
            'current_password' => ['required', 'string', 'current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'The provided password does not match your current password.',
        ];
    }
}
