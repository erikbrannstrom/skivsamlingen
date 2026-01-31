<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for user registration.
 */
class RegisterRequest extends FormRequest
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
            'username' => [
                'required',
                'min:3',
                'max:24',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('users', 'username'),
            ],
            'password' => [
                'required',
                'min:6',
            ],
            'password_confirmation' => [
                'required',
                'same:password',
            ],
            'captcha' => [
                'required',
                function ($attribute, $value, $fail) {
                    $captchaA = $this->input('captcha_a');
                    $captchaB = $this->input('captcha_b');
                    $correctAnswer = $this->getCaptchaValue($captchaA) + $this->getCaptchaValue($captchaB);

                    if ((int) $value !== $correctAnswer) {
                        $fail($this->messages()['captcha.correct']);
                    }
                },
            ],
            'email' => [
                'nullable',
                'email',
                'max:64',
            ],
            'name' => [
                'nullable',
                'max:50',
            ],
            'sex' => [
                'nullable',
                Rule::in(['f', 'm', 'x']),
            ],
            'birth' => [
                'nullable',
                'date_format:Y-m-d',
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
            'username.required' => 'Användarnamn måste anges.',
            'username.min' => 'Användarnamnet måste vara minst :min tecken.',
            'username.max' => 'Användarnamnet får vara max :max tecken.',
            'username.regex' => 'Användarnamnet får endast innehålla a-z, 0-9, streck, understreck och punkt.',
            'username.unique' => 'Användarnamnet är redan taget.',
            'password.required' => 'Lösenord måste anges.',
            'password.min' => 'Lösenordet måste vara minst :min tecken.',
            'password_confirmation.required' => 'Lösenordskontroll måste anges.',
            'password_confirmation.same' => 'Lösenorden matchar inte.',
            'captcha.required' => 'Robotfiltret måste fyllas i.',
            'captcha.correct' => 'Fel svar. Är du verkligen en människa?',
            'email.email' => 'E-postadressen är inte giltig.',
            'email.max' => 'E-postadressen får vara max :max tecken.',
            'name.max' => 'Namnet får vara max :max tecken.',
            'sex.in' => 'Ogiltigt val för kön.',
            'birth.date_format' => 'Födelsedagen måste anges som ÅÅÅÅ-MM-DD.',
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
            'username' => 'användarnamn',
            'password' => 'lösenord',
            'password_confirmation' => 'lösenordskontroll',
            'captcha' => 'robotfilter',
            'email' => 'e-post',
            'name' => 'namn',
            'sex' => 'kön',
            'birth' => 'födelsedag',
        ];
    }

    /**
     * Swedish number words for captcha.
     */
    private const CAPTCHA_VALUES = [
        'noll' => 0,
        'ett' => 1,
        'två' => 2,
        'tre' => 3,
        'fyra' => 4,
        'fem' => 5,
        'sex' => 6,
        'sju' => 7,
        'åtta' => 8,
        'nio' => 9,
        'tio' => 10,
        'elva' => 11,
    ];

    /**
     * Get numeric value from Swedish captcha word.
     */
    private function getCaptchaValue(?string $word): int
    {
        if ($word === null) {
            return 0;
        }
        return self::CAPTCHA_VALUES[strtolower($word)] ?? 0;
    }
}
