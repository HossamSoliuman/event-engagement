<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DownloadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_id' => ['nullable', 'integer', Rule::exists('events', 'id')],
            'media_type' => ['required', Rule::in(['all', 'photo', 'video'])],
            'status' => ['required', Rule::in(['all', 'pending', 'approved', 'rejected'])],
            'include_manifest' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'event_id.exists' => 'The selected event is no longer available.',
            'media_type.in' => 'Choose all media, images only, or videos only.',
            'status.in' => 'Choose a valid moderation status.',
        ];
    }
}
