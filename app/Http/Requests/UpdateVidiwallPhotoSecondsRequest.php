<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVidiwallPhotoSecondsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'seconds' => ['required', 'integer', 'min:1', 'max:60'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'seconds.required' => 'Enter how many seconds each photo stays on screen.',
            'seconds.integer' => 'Photo seconds must be a whole number.',
            'seconds.min' => 'Photos need at least 1 second on screen.',
            'seconds.max' => 'Photos can stay on screen for 60 seconds at most.',
        ];
    }
}
