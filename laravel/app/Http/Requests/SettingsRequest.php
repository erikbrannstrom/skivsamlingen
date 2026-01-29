<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for updating user settings.
 */
class SettingsRequest extends FormRequest
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
            'name' => [
                'nullable',
                'max:50',
            ],
            'sex' => [
                'nullable',
                Rule::in(['f', 'm', 'x']),
            ],
            'email' => [
                'nullable',
                'email',
                'max:64',
            ],
            'public_email' => [
                'nullable',
                Rule::in(['0', '1']),
            ],
            'birth' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'about' => [
                'nullable',
                'max:3000',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
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
            'name.max' => 'Namnet får vara max :max tecken.',
            'sex.in' => 'Ogiltigt val för kön.',
            'email.email' => 'E-postadressen är inte giltig.',
            'email.max' => 'E-postadressen får vara max :max tecken.',
            'public_email.in' => 'Ogiltigt val för publik e-post.',
            'birth.date_format' => 'Födelsedagen måste anges som ÅÅÅÅ-MM-DD.',
            'about.max' => 'Om mig-texten får vara max :max tecken.',
            'per_page.integer' => 'Skivor per sida måste vara ett heltal.',
            'per_page.min' => 'Skivor per sida måste vara minst :min.',
            'per_page.max' => 'Skivor per sida får vara max :max.',
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
            'name' => 'namn',
            'sex' => 'kön',
            'email' => 'e-post',
            'public_email' => 'publik e-post',
            'birth' => 'födelsedag',
            'about' => 'om mig',
            'per_page' => 'skivor per sida',
        ];
    }
}
