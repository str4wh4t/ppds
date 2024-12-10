<?php

namespace App\Http\Requests\Student;

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
            'semester' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'student_unit_id' => ['required', 'integer', 'exists:units,id'],
            'dosbing_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'doswal_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];

        return array_merge($rules, $additionalRules);
    }
}
