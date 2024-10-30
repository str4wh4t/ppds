<?php

namespace App\Http\Requests\User;

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
            'fullname' => [
                'required',
                'string',
                'max:255',
            ],
            'username' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9]+$/',
                Rule::unique(User::class)->ignore($this->user->id),
            ],
            'identity' => [
                'required',
                'string',
                'max:255',
                Rule::unique(User::class)->ignore($this->user->id),
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user->id),
            ],
            'roles' => [
                'required',
                'array',
            ],
            'roles.*' => [
                'string',
                Rule::in(Role::pluck('name')->toArray()), // Validasi `roles.*` agar sesuai dengan daftar yang diizinkan
            ],
        ];
    }
}
