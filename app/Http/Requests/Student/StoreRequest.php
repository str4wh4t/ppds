<?php

namespace App\Http\Requests\Student;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class StoreRequest extends \App\Http\Requests\User\StoreRequest
{
    public function rules(): array
    {
        // Mengambil rules dari parent class
        $rules = parent::rules();

        // Modifikasi rules pengurangan atau penambahan jika diperlukan
        $keysToRemove = [
            'roles' => '',
            'roles.*' => '',
        ];
        $rules = array_diff_key($rules, $keysToRemove);

        $additionalRules = [
            // 'field1' => ['required', 'string', 'max:255'],
            'semester' => [
                'required',
                'integer',
                'min:1',
            ],
            'student_unit_id' => ['required', 'integer', 'exists:units,id'],
            'dosbing_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];

        // Menggabungkan rules dari parent dengan additionalRules
        return array_merge($rules, $additionalRules);
    }
}
