<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get currently authenticated user
        $user = auth()->user();

        // Get user being updated
        $selectedUserId = (int) $this->route('user');
        $selectedUser = User::find($selectedUserId);

        // Check the role of the user being updated
        // Admin permissions
        if ($user->hasPermissionTo('edit client or staff users only') && $selectedUser->hasAnyRole(['client', 'staff'])) {
            return true;
        }

        // Staff permissions
        if ($user->hasPermissionTo('edit client users only') && $selectedUser->hasRole('client')) {
            return true;
        }

        // If updating own user data
        if ($user->hasPermissionTo('edit own user profile') && $selectedUser->id === $user->id) {
            return true;
        }

        // Clients cannot update other users
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string'],
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($this->user)],
            'password' => ['sometimes', 'required', 'string', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name must not be empty.',
            'email.required' => 'Email must not be empty.',
            'password.required' => 'Password must not be empty.',
            'password' => 'Password must be at least 8 characters long.',
        ];
    }
}
