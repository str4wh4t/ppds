<?php

namespace App\Http\Requests\Speak;

use App\Models\Speak;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'speak_title' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Speak::class),
            ],
            'description' => [
                'required',
                'string',
            ],
            'document' => 'nullable|file|mimes:jpeg|max:10240',
            'speak_document_path' => [
                'nullable',
                'string',
                'max:255',
            ]
        ];
    }
}
