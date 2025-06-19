<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            //
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'string|max:255|in:admin,client,owner',
            'phone_number' => 'required|unique:users,phone_number|string|max:11|min:11',
            'national_id' => 'required|unique:users,national_id|string|max:14|min:14',
            'location' => 'nullable|string',
    
        ];

    }

    public function messages(): array
    {
        return [
            'name.required'     => 'The name field is required.',
            'email.required'    => 'Please enter your email address.',
            'email.email'       => 'The email format is invalid.',
            'email.unique'      => 'This email is already registered.',
            'password.required' => 'You must enter a password.',
            'password.min'      => 'Password must be at least 6 characters.',
            'password.confirmed'=> 'Passwords do not match.',
            'role.string'       => 'The role must be a string.',
            'role.max'          => 'The role must not be greater than 255 characters.',
            'role.in'           => 'The role must be one of the following: client, owner.',
            'phone_number.required' => 'The phone number field is required.',
            'phone_number.unique' => 'This phone number is already registered.',
            'phone_number.string' => 'The phone number must be a string.',
            'phone_number.max' => 'The phone number must not be greater than 11 characters.',
            'phone_number.min' => 'The phone number must not be less than 11 characters.',
            'national_id.required' => 'The national id field is required.',
            'national_id.unique' => 'This national id is already registered.',
            'national_id.string' => 'The national id must be a string.',
            'national_id.max' => 'The national id must not be greater than 14 characters.',
            'national_id.min' => 'The national id must not be less than 14 characters.',
        ];
    }
}
