<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $categoryId = (int) $this->route('category');
        return auth()->user()->can('update', Category::find($categoryId));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string'],
            'description' => ['sometimes', 'required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Title cannot be empty.',
            'description.required' => 'Description cannot be empty.',
        ];
    }
}
