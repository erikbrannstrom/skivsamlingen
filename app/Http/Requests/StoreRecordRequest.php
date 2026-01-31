<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'artist' => 'required|max:64',
            'title' => 'required|max:150',
            'year' => 'nullable|integer|digits:4',
            'format' => 'nullable|max:30',
            'comment' => 'nullable|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'artist.required' => 'Artist/grupp måste anges.',
            'artist.max' => 'Artist/grupp får vara max :max tecken.',
            'title.required' => 'Titel måste anges.',
            'title.max' => 'Titeln får vara max :max tecken.',
            'year.integer' => 'År måste vara ett heltal.',
            'year.digits' => 'År måste vara fyra siffror.',
            'format.max' => 'Formatet får vara max :max tecken.',
            'comment.max' => 'Kommentaren får vara max :max tecken.',
        ];
    }
}
