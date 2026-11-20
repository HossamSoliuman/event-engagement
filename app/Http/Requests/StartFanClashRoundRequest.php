<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Models\FanClashScreen;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Starting a round needs a matchup only when none is up on the console yet.
 */
class StartFanClashRoundRequest extends PrepareFanClashRoundRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if (! $this->hasMatchupUp()) {
            return parent::rules();
        }

        return [
            'duration_seconds' => ['nullable', 'integer', Rule::in(FanClashScreen::ROUND_SECONDS)],
        ];
    }

    private function hasMatchupUp(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event && $event->fanClashScreen?->round?->status === 'waiting';
    }
}
