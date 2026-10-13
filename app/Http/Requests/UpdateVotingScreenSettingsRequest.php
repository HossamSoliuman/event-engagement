<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVotingScreenSettingsRequest extends FormRequest
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
            'headline' => ['nullable', 'string', 'max:60'],
            'show_everyone' => ['nullable', 'boolean'],
            'top_count' => ['exclude_if:show_everyone,1', 'required', 'integer', 'min:1', 'max:20'],
            'countdown_seconds' => ['required', 'integer', 'min:0', 'max:60'],
            'hold_result' => ['nullable', 'boolean'],
            'result_seconds' => ['exclude_if:hold_result,1', 'required', 'integer', 'min:3', 'max:600'],
            'show_votes' => ['nullable', 'boolean'],
            'show_percent' => ['nullable', 'boolean'],
            'show_total' => ['nullable', 'boolean'],
            'show_position' => ['nullable', 'boolean'],
            'show_qr' => ['nullable', 'boolean'],
            'confetti' => ['nullable', 'boolean'],
            'close_on_reveal' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'headline.max' => 'Keep the headline to 60 characters.',
            'top_count.required' => 'Enter how many places the live results show, or show everyone.',
            'top_count.integer' => 'The number of places must be a whole number.',
            'top_count.min' => 'Show at least the top place.',
            'top_count.max' => 'The live results can show the top 20 places at most. Show everyone for more.',
            'countdown_seconds.required' => 'Enter how long the countdown runs.',
            'countdown_seconds.integer' => 'The countdown must be a whole number of seconds.',
            'countdown_seconds.min' => 'The countdown cannot be negative. Use 0 to skip it.',
            'countdown_seconds.max' => 'The countdown can run for 60 seconds at most.',
            'result_seconds.required' => 'Enter how long the winner stays on screen, or keep it up until you hide it.',
            'result_seconds.integer' => 'The winner time must be a whole number of seconds.',
            'result_seconds.min' => 'Show the winner for at least 3 seconds.',
            'result_seconds.max' => 'The winner can stay on screen for 600 seconds at most. Keep it up until you hide it for longer.',
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
            'headline' => $headline === '' ? null : $headline,
            'top_count' => $this->boolean('show_everyone') ? 0 : $this->integer('top_count'),
            'countdown_seconds' => $this->integer('countdown_seconds'),
            'result_seconds' => $this->boolean('hold_result') ? 0 : $this->integer('result_seconds'),
            'show_votes' => $this->boolean('show_votes'),
            'show_percent' => $this->boolean('show_percent'),
            'show_total' => $this->boolean('show_total'),
            'show_position' => $this->boolean('show_position'),
            'show_qr' => $this->boolean('show_qr'),
            'confetti' => $this->boolean('confetti'),
            'close_on_reveal' => $this->boolean('close_on_reveal'),
        ];
    }
}
