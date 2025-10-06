<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->hasAnyPermission(['create a user', 'create client users only', 'create client and staff users only']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(6)],
            'role' => ['required', 'string', Rule::in(['admin', 'staff', 'client'])],
            'status' => ['sometimes', 'string', Rule::in(['active', 'suspended', 'banned'])],
            'email_verified_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'email.unique' => 'Email already exists.',
            'password.required' => 'Password is required.',
            'password' => 'Password must be at least 8 characters long.',
            'role.required' => 'User role required.',
            'role' => "Invalid role. Please choose either admin, staff, or client role.",
            'status' => 'Invalid status. Please choose either active, suspended or banned.',
        ];
    }
}
