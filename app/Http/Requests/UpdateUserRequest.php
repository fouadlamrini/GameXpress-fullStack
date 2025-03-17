<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit_users');
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $this->route('user')->id,
            'password' => 'sometimes|string|min:8|confirmed',
            'roles' => 'sometimes|array|exists:roles,name'
        ];
    }
}