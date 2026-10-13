<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReorderVidiwallPlaylistRequest extends FormRequest
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
            'order.required' => 'Send the playlist in its new order.',
            'order.array' => 'The playlist order is invalid.',
            'order.*.integer' => 'The playlist order is invalid.',
            'order.*.distinct' => 'The playlist order is invalid.',
        ];
    }

    /**
     * @return list<int>
     */
    public function orderedIds(): array
    {
        return array_map('intval', array_values((array) $this->input('order', [])));
    }
}
