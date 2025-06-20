<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // You can customize if needed (e.g., policies)
    }

    public function rules(): array
    {
        return [
            'book_id' => 'required|exists:books,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ];
    }
    public function messages(): array
    {
        return [
            'book_id.required' => 'The book ID is required.',
            'book_id.exists' => 'The selected book does not exist.',
            'rating.required' => 'A rating is required.',
            'rating.integer' => 'The rating must be an integer.',
            'rating.min' => 'The rating must be at least 1.',
            'rating.max' => 'The rating must not exceed 5.',
            'comment.string' => 'The comment must be a string.',
        ];
    }
}
