<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_products');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'status' => 'required|in:available,unavailable',
            'category_id' => 'required_without:sub_category_id|exists:categories,id',
            'sub_category_id' => 'exists:sub_categories,id|required_without:category_id',
            'critical_stock_threshold' => 'required|integer|min:0',
        ];
    }
}
