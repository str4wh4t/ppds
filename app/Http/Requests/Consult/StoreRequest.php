<?php

namespace App\Http\Requests\Consult;

use App\Models\Consult;
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
            'consult_title' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Consult::class),
            ],
            'description' => [
                'required',
                'string',
            ],
            'document' => 'nullable|file|mimes:pdf|max:10240',
            'consult_document_path' => [
                'nullable',
                'string',
                'max:255',
            ]
        ];
    }
}
