<?php

namespace App\Http\Requests;

use App\Models\Joke;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class UpdateJokeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Staff level and higher can edit any joke.
        if (auth()->user()->hasPermissionTo('edit any joke')) {
            return true;
        }

        // Client users can only update jokes belonging to themselves.
        $jokeId = (int) $this->route('joke');
        $userId = Joke::find($jokeId)->user_id;

        return auth()->user()->hasPermissionTo('edit own joke') && auth()->user()->id === $userId;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:96'],
            'content' => ['sometimes', 'required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.max' => 'Joke title must be less than 96 characters.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json(
                [
                    'message' => "Please fix validation errors",
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422)
        );
    }
}
