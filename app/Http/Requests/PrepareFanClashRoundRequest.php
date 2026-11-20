<?php

namespace App\Http\Requests;

use App\Models\FanClashScreen;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrepareFanClashRoundRequest extends FormRequest
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
            'fan_clash_matchup_id' => ['nullable', 'integer'],
            'category' => ['nullable', 'string', 'max:60'],
            'side_a_name' => ['required_without:fan_clash_matchup_id', 'nullable', 'string', 'max:60'],
            'side_b_name' => ['required_without:fan_clash_matchup_id', 'nullable', 'string', 'max:60'],
            'side_a_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'side_b_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'duration_seconds' => ['nullable', 'integer', Rule::in(FanClashScreen::ROUND_SECONDS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fan_clash_matchup_id.integer' => 'Pick one of the saved matchups.',
            'category.max' => 'Keep the category to 60 characters.',
            'side_a_name.required_without' => 'Pick a saved matchup, or type both contender names.',
            'side_b_name.required_without' => 'Pick a saved matchup, or type both contender names.',
            'side_a_name.max' => 'Keep each name to 60 characters.',
            'side_b_name.max' => 'Keep each name to 60 characters.',
            'side_a_color.regex' => 'Pick a colour for side A.',
            'side_b_color.regex' => 'Pick a colour for side B.',
            'duration_seconds.integer' => 'Pick how long the round runs.',
            'duration_seconds.in' => 'Pick one of the round lengths on offer.',
        ];
    }
}
