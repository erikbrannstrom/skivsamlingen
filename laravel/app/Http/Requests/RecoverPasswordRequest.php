<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for password recovery (setting new password after clicking reset link).
 */
class RecoverPasswordRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'min:6',
            ],
            'password_confirmation' => [
                'required',
                'same:password',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Nytt lösenord måste anges.',
            'password.min' => 'Lösenordet måste vara minst :min tecken.',
            'password_confirmation.required' => 'Lösenordskontroll måste anges.',
            'password_confirmation.same' => 'Lösenorden matchar inte.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'password' => 'nytt lösenord',
            'password_confirmation' => 'kontrollfältet',
        ];
    }
}
