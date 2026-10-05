<?php

namespace App\Http\Requests;

use App\Models\SurveyQuestion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSurveyQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Drop the blank option rows the admin form always renders, so "3 options"
     * means three filled-in inputs regardless of how many slots were shown.
     */
    protected function prepareForValidation(): void
    {
        $options = collect((array) $this->input('options', []))
            ->map(fn ($option) => trim((string) $option))
            ->filter(fn (string $option) => $option !== '')
            ->values()
            ->all();

        $this->merge(['options' => $options]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isChoice = $this->input('type') === SurveyQuestion::TYPE_CHOICE;

        return [
            'question' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in([SurveyQuestion::TYPE_CHOICE, SurveyQuestion::TYPE_TEXT])],
            'options' => $isChoice
                ? ['array', 'min:'.SurveyQuestion::MIN_OPTIONS, 'max:'.SurveyQuestion::MAX_OPTIONS]
                : ['array'],
            'options.*' => ['string', 'max:120'],
            'is_required' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'question.required' => 'Type the question fans should answer.',
            'type.in' => 'Choose multiple choice or free text.',
            'options.min' => 'A multiple-choice question needs at least '.SurveyQuestion::MIN_OPTIONS.' answer options.',
            'options.max' => 'A multiple-choice question can have at most '.SurveyQuestion::MAX_OPTIONS.' answer options.',
            'options.*.max' => 'Answer options can be at most 120 characters.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $options = (array) $this->input('options', []);

            if (count($options) !== count(array_unique(array_map('mb_strtolower', $options)))) {
                $validator->errors()->add('options', 'Each answer option must be different.');
            }
        });
    }

    /**
     * The attributes to persist, normalised for the question type.
     *
     * @return array{question: string, type: string, options: list<string>|null, is_required: bool}
     */
    public function questionAttributes(): array
    {
        $type = $this->validated('type');

        return [
            'question' => trim($this->validated('question')),
            'type' => $type,
            'options' => $type === SurveyQuestion::TYPE_CHOICE ? $this->validated('options') : null,
            'is_required' => $this->boolean('is_required'),
        ];
    }
}
