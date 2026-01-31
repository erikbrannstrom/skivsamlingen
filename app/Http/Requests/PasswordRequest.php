<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

/**
 * Form request for changing password.
 */
class PasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(redirect('/account/login'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                function ($attribute, $value, $fail) {
                    $user = Auth::user();
                    if (!$user || !$user->verifyPassword($value)) {
                        $fail($this->messages()['current_password.correct']);
                    }
                },
            ],
            'new_password' => [
                'required',
                'min:6',
            ],
            'new_password_confirmation' => [
                'required',
                'same:new_password',
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
            'current_password.required' => 'Nuvarande lösenord måste anges.',
            'current_password.correct' => 'Ditt nuvarande lösenord var felaktigt.',
            'new_password.required' => 'Nytt lösenord måste anges.',
            'new_password.min' => 'Det nya lösenordet måste vara minst :min tecken.',
            'new_password_confirmation.required' => 'Lösenordskontroll måste anges.',
            'new_password_confirmation.same' => 'Lösenorden matchar inte.',
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
            'current_password' => 'nuvarande lösenord',
            'new_password' => 'nytt lösenord',
            'new_password_confirmation' => 'lösenordskontroll',
        ];
    }
}
