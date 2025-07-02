<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'isbn' => 'nullable|string|max:20',
            'pages' => 'nullable|integer|min:1',
            'author' => 'nullable|string|max:255',
            'description' => 'sometimes|required|string',
            'condition' => 'sometimes|required|in:new,like-new,good,fair,poor',
            'price' => 'sometimes|required|numeric|min:0',
            'rental_price' => 'nullable|numeric|min:0',
            'educational_level' => 'nullable|string|max:50',
            'genre' => 'nullable|string|max:100',
            'category_id' => 'sometimes|required|exists:categories,id',
            'status' => 'sometimes|required|in:available,rented,sold',
            'quantity' => 'sometimes|required|integer|min:1',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
}
