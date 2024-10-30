<?php

namespace App\Http\Requests\Dosen;

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
            'dosen_units' => ['required', 'array', 'min:1'],
            'dosen_units.*.id' => ['required', 'integer', 'exists:units,id'], // Memvalidasi bahwa setiap id ada di tabel units
        ];

        // Menggabungkan rules dari parent dengan additionalRules
        return array_merge($rules, $additionalRules);
    }
}
