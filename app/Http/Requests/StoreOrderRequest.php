<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
        'items' => 'required|array|min:1',
        'items.*.book_id' => 'required|exists:books,id',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.price' => 'required|numeric|min:0',
        ];
    }

    public function messages()
    {
        return [
            'items.required' => 'Order items are required.',
            'items.array' => 'Order items must be an array.',
            'items.min' => 'At least one order item is required.',
            'items.*.book_id.required' => 'Book ID is required for each item.',
            'items.*.book_id.exists' => 'The selected book does not exist.',
            'items.*.quantity.required' => 'Quantity is required for each item.',
            'items.*.quantity.integer' => 'Quantity must be an integer.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.price.required' => 'Price is required for each item.',
            'items.*.price.numeric' => 'Price must be a number.',
            'items.*.price.min' => 'Price must be at least 0.',
        ];
    }
}