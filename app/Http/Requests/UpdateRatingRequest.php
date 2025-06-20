<?php

namespace App\Http\Requests;
use App\Models\Rating;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Check if the user is authorized to update the rating
        // Assuming the user must be the reviewer or an admin to update the rating
        $user = auth()->user();
        $rating = Rating::find($this->route('id')); // Assuming the rating ID is passed in the route

        if (!$rating) {
            return false;
        }

        return $user->id === $rating->reviewer_id || $user->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ];
    }
    public function messages(): array
    {
        return [
            'rating.required' => 'A rating is required.',
            'rating.integer' => 'The rating must be an integer.',
            'rating.min' => 'The rating must be at least 1.',
            'rating.max' => 'The rating must not exceed 5.',
            'comment.string' => 'The comment must be a string.',
            'comment.max' => 'The comment may not be greater than 1000 characters.',
        ];
    }
}
