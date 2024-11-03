<?php

namespace App\Http\Requests\Permission;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

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
            'roles' => [
                'nullable',
                'array',
            ],
            'roles.*' => [
                'string',
                Rule::in(Role::pluck('name')->toArray()), // Validasi `roles.*` agar sesuai dengan daftar yang diizinkan
            ],
        ];
    }
}
