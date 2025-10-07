<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get user being updated
        $selectedUserId = (int) $this->route('user');
        $selectedUser = User::find($selectedUserId);

        return auth()->user()->can('update', $selectedUser);
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
            'role' => ['sometimes', 'required', 'string', Rule::in(['admin', 'staff', 'client'])],
            'status' => ['sometimes', 'required', 'string', Rule::in(['active', 'suspended', 'banned'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name must not be empty.',
            'email.required' => 'Email must not be empty.',
            'role.required' => 'Role must not be empty.',
            'role' => 'Role can either be admin, staff or client.',
            'status.required' => 'Status must not be empty.',
            'status' => 'Status can either be active, suspended or banned.',
        ];
    }
}
