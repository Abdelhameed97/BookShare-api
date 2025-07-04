<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderItemQuantityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'book_id' => 'required|exists:books,id',
            'quantity' => 'required|integer|min:1',
            'type' => 'required|in:buy,rent',
        ];
    }
    public function messages()
    {
        return [
            'type.required' => 'Purchase type (buy/rent) is required.',
            'type.in' => 'Purchase type must be either buy or rent.',
        ];
    }
}
