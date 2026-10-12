<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReorderSurveyQuestionsRequest extends FormRequest
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
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order.required' => 'Send the questions in their new order.',
            'order.*.integer' => 'The question order is invalid.',
            'order.*.distinct' => 'The question order is invalid.',
        ];
    }

    /**
     * The order must list exactly this event's questions, so a page left open
     * while someone else added or removed a question can't scramble positions.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Event $event */
            $event = $this->route('event');
            $current = $event->surveyQuestions()->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();

            if ($this->orderedIds() === [] || collect($this->orderedIds())->sort()->values()->all() !== $current) {
                $validator->errors()->add('order', 'The question list changed in the meantime. Reload the page and try again.');
            }
        });
    }

    /**
     * @return list<int>
     */
    public function orderedIds(): array
    {
        return array_map('intval', array_values((array) $this->input('order', [])));
    }
}
