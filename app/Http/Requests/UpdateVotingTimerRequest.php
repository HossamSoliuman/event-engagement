<?php

namespace App\Http\Requests;

use App\Models\VotingScreen;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVotingTimerRequest extends FormRequest
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
            'minutes' => ['required', 'integer', Rule::in(VotingScreen::VOTING_MINUTES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'minutes.required' => 'Pick how long voting stays open.',
            'minutes.integer' => 'Pick how long voting stays open.',
            'minutes.in' => 'Pick one of the voting times on offer.',
        ];
    }
}
