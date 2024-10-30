<?php

namespace App\Http\Requests\Unit;

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
            'kaprodi_user_id' => [
                'nullable',
                'exists:users,id',
            ],
            'unit_admins' => ['nullable', 'array'],
            'unit_admins.*.id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
