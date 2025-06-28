<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        Log::info('Incoming payment request', $this->all());

        return [
            'order_id' => 'required|exists:orders,id',
            'method' => 'required|in:cash,card,stripe,paypal',
        ];
    }

    /**
     * Customize the validation error messages (optional).
     */
    public function messages(): array
    {
        return [
            'order_id.required' => 'Order ID is required.',
            'order_id.exists'   => 'The selected order does not exist.',
            'method.required'   => 'Payment method is required.',
            'method.in'         => 'Payment method must be either cash or card.',
        ];
    }
}
