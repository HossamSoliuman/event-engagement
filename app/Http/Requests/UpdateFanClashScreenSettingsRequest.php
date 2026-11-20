<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFanClashScreenSettingsRequest extends FormRequest
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
            'countdown_seconds' => ['required', 'integer', 'min:0', 'max:10'],
            'show_taps' => ['nullable', 'boolean'],
            'show_qr' => ['nullable', 'boolean'],
            'confetti' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'countdown_seconds.required' => 'Enter how long the countdown before the start runs.',
            'countdown_seconds.integer' => 'The countdown must be a whole number of seconds.',
            'countdown_seconds.min' => 'The countdown cannot be negative. Use 0 to skip it.',
            'countdown_seconds.max' => 'The countdown can run for 10 seconds at most.',
        ];
    }

    /**
     * The settings as they are stored, with unticked boxes saved as off.
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        return [
            'countdown_seconds' => $this->integer('countdown_seconds'),
            'show_taps' => $this->boolean('show_taps'),
            'show_qr' => $this->boolean('show_qr'),
            'confetti' => $this->boolean('confetti'),
        ];
    }
}
