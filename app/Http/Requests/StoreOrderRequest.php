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
            'items.*.type' => 'required|in:buy,rent',
        ];
    }

    public function messages()
    {
        return [
            'items.required' => 'Order items are required.',
            'items.array' => 'Order items must be an array.',
            'items.min' => 'At least one order item is required.',
            'items.*.book_id.required' => 'Book ID is required for each item.',
            'items.*.book_id.exists' => 'Selected book does not exist.',
            'items.*.quantity.required' => 'Quantity is required for each item.',
            'items.*.quantity.integer' => 'Quantity must be an integer.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.type.required' => 'Purchase type (buy/rent) is required for each item.',
            'items.*.type.in' => 'Purchase type must be either buy or rent.'
        ];
    }
}