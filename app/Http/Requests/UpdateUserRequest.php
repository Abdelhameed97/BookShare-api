<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $user_id = $this->route('id'); // Get the user ID from the route

        return [
            'name'     => 'sometimes|required|string|max:255',

            'email'    => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user_id),
            ],

            'password' => 'sometimes|required|string|min:6|confirmed',

            'role'     => 'sometimes|string|max:255|in:admin,client,owner',

            'phone_number' => [
                'sometimes',
                'required',
                'string',
                'min:11',
                'max:11',
                Rule::unique('users', 'phone_number')->ignore($user_id),
            ],

            'national_id' => [
                'sometimes',
                'required',
                'string',
                'min:14',
                'max:14',
                Rule::unique('users', 'national_id')->ignore($user_id),
            ],

            'location' => 'sometimes|required|string',
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
            'role.in'           => 'Invalid role selected.',
            'phone_number.required' => 'Please enter your phone number.',
            'phone_number.unique' => 'This phone number is already registered.',
            'phone_number.max' => 'Phone number must be 11 digits.',
            'phone_number.min' => 'Phone number must be 11 digits.',
            'national_id.required' => 'Please enter your national id.',
            'national_id.unique' => 'This national id is already registered.',
            'national_id.max' => 'National id must be 14 digits.',
            'national_id.min' => 'National id must be 14 digits.',
            'location.required' => 'Please enter your location.',
        ];
    }
}
