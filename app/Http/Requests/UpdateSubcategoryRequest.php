<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit_categories');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

}
