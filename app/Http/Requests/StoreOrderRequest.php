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
            'client_id' => 'required|exists:clients,id',
            'owner_id' => 'required|exists:owners,id',
            'status' => 'sometimes|in:pending,accepted,rejected,delivered',
            'payment_method' => 'sometimes|in:cash,card',
        ];
    }
}
