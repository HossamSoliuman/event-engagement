<?php

namespace App\Http\Requests;

use App\Models\SurveyQuestion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Each answer is a string, or a list of picked options for a question
     * that allows several answers. The controller checks which shape fits.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'max:'.SurveyQuestion::MAX_ANSWER_LENGTH],
            'answers.*.*' => ['string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.required' => 'Please answer the survey before sending it.',
            'answers.*.max' => 'Answers can be at most '.SurveyQuestion::MAX_ANSWER_LENGTH.' characters.',
            'answers.*.*.string' => 'Please pick from the listed options.',
            'answers.*.*.max' => 'Please pick from the listed options.',
        ];
    }
}
