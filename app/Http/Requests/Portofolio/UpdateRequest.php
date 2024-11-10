<?php

namespace App\Http\Requests\Portofolio;

use App\Models\Portofolio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Portofolio::class)
                    ->where('user_id', $this->user()->id)
                    ->ignore($this->portofolio->id),
            ],
            'description' => [
                'required',
                'string',
            ],
            'document' => 'sometimes|file|mimes:pdf|max:10240',
            'portofolio_document_path' => [
                'sometimes',
                'string',
                'max:255',
            ]
        ];
    }
}
