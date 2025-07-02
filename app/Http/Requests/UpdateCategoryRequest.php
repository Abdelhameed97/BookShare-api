<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255|unique:categories,name,' . $this->route('category'),
            'description' => 'sometimes|required|string|max:1000', // Assuming description is a string with a max length
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'name.unique' => 'This category name is already taken.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'description.required' => 'The description field is required.',
            'description.max' => 'The description may not be greater than 1000 characters.',
        ];
    }
}

