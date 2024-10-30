<?php

namespace App\Http\Requests\Dosen;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UpdateRequest extends \App\Http\Requests\User\UpdateRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $keysToRemove = [
            'roles' => '',
            'roles.*' => '',
        ];
        $rules = array_diff_key($rules, $keysToRemove);

        $additionalRules = [
            // 'field1' => ['required', 'string', 'max:255'],
            'dosen_units' => ['required', 'array', 'min:1'],
            'dosen_units.*.id' => ['required', 'integer', 'exists:units,id'], // Memvalidasi bahwa setiap id ada di tabel units
        ];

        return array_merge($rules, $additionalRules);
    }
}
