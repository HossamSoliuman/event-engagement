<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DrawLotteryWinnerRequest extends FormRequest
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
            'prize' => ['nullable', 'string', 'max:120'],
            'on_screen' => ['nullable', 'boolean'],
            'next_rank' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prize.string' => 'The prize must be text.',
            'prize.max' => 'Keep the prize name to 120 characters.',
            'on_screen.boolean' => 'Choose whether the draw shows on the vidiwall.',
            'next_rank.integer' => 'The draw number is not valid. Reload the page and try again.',
            'next_rank.min' => 'The draw number is not valid. Reload the page and try again.',
        ];
    }

    public function prize(): ?string
    {
        $prize = trim((string) $this->input('prize'));

        return $prize === '' ? null : $prize;
    }
}
