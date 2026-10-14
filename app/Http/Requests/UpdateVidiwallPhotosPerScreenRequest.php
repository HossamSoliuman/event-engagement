<?php

namespace App\Http\Requests;

use App\Models\VidiwallPlayer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVidiwallPhotosPerScreenRequest extends FormRequest
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
            'count' => ['bail', 'required', 'integer', Rule::in(VidiwallPlayer::PHOTOS_PER_SCREEN)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $counts = VidiwallPlayer::PHOTOS_PER_SCREEN;
        $last = array_pop($counts);

        return [
            'count.required' => 'Choose how many photos show on screen at once.',
            'count.integer' => 'Photos on screen must be a whole number.',
            'count.in' => 'The screen can show '.implode(', ', $counts).' or '.$last.' photos at once.',
        ];
    }
}
