<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

/**
 * Form request for account deletion (unregister).
 */
class UnregisterRequest extends FormRequest
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
            'password' => [
                'required',
                function ($attribute, $value, $fail) {
                    $user = Auth::user();
                    if (!$user || !$user->verifyPassword($value)) {
                        $fail($this->messages()['password.correct']);
                    }
                },
            ],
            'confirmation' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (strtolower(trim($value)) !== 'ta bort') {
                        $fail($this->messages()['confirmation.correct']);
                    }
                },
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
            'password.required' => 'Lösenord måste anges.',
            'password.correct' => 'Lösenordet är felaktigt.',
            'confirmation.required' => 'Bekräftelse måste anges.',
            'confirmation.correct' => 'Skriv "ta bort" för att bekräfta.',
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
            'password' => 'lösenord',
            'confirmation' => 'bekräftelse',
        ];
    }
}
