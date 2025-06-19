<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status' => 'sometimes|in:pending,accepted,rejected,delivered',
            'payment_method' => 'sometimes|in:cash,card',
        ];
    }
}
