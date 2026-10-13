<?php

namespace App\Http\Requests;

use App\Models\LotteryScreen;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLotteryScreenSettingsRequest extends FormRequest
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
            'countdown_seconds' => ['required', 'integer', 'min:0', 'max:60'],
            'drawing_seconds' => ['required', 'integer', 'min:1', 'max:30'],
            'hold_result' => ['nullable', 'boolean'],
            'result_seconds' => ['exclude_if:hold_result,1', 'required', 'integer', 'min:3', 'max:600'],
            'headline' => ['nullable', 'string', 'max:60'],
            'name_format' => ['required', Rule::in([LotteryScreen::NAME_FULL, LotteryScreen::NAME_SHORT])],
            'show_prize' => ['nullable', 'boolean'],
            'show_phone' => ['nullable', 'boolean'],
            'show_entry_count' => ['nullable', 'boolean'],
            'confetti' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'countdown_seconds.required' => 'Enter how long the countdown runs.',
            'countdown_seconds.integer' => 'The countdown must be a whole number of seconds.',
            'countdown_seconds.min' => 'The countdown cannot be negative. Use 0 to skip it.',
            'countdown_seconds.max' => 'The countdown can run for 60 seconds at most.',
            'drawing_seconds.required' => 'Enter how long the drawing runs.',
            'drawing_seconds.integer' => 'The drawing must be a whole number of seconds.',
            'drawing_seconds.min' => 'The drawing needs at least 1 second.',
            'drawing_seconds.max' => 'The drawing can run for 30 seconds at most.',
            'result_seconds.required' => 'Enter how long the winner stays on screen, or keep it up until you hide it.',
            'result_seconds.integer' => 'The result time must be a whole number of seconds.',
            'result_seconds.min' => 'Show the winner for at least 3 seconds.',
            'result_seconds.max' => 'The winner can stay on screen for 600 seconds at most. Keep it up until you hide it for longer.',
            'headline.max' => 'Keep the headline to 60 characters.',
            'name_format.required' => 'Choose how winner names show on screen.',
            'name_format.in' => 'Choose how winner names show on screen.',
        ];
    }

    /**
     * The settings as they are stored, with unticked boxes saved as off.
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $headline = trim((string) $this->input('headline'));

        return [
            'countdown_seconds' => $this->integer('countdown_seconds'),
            'drawing_seconds' => $this->integer('drawing_seconds'),
            'result_seconds' => $this->boolean('hold_result') ? 0 : $this->integer('result_seconds'),
            'headline' => $headline === '' ? null : $headline,
            'name_format' => $this->input('name_format'),
            'show_prize' => $this->boolean('show_prize'),
            'show_phone' => $this->boolean('show_phone'),
            'show_entry_count' => $this->boolean('show_entry_count'),
            'confetti' => $this->boolean('confetti'),
        ];
    }
}
