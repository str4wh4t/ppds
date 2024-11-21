<?php

namespace App\Http\Requests\Stase;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class StoreRequest extends FormRequest
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
            'stase_location_id' => [
                'required',
                'exists:stase_locations,id',
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
