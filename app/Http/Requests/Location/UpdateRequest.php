<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
                'required_with:longitude',
            ],
            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
                'required_with:latitude',
            ],
        ];
    }
}
