<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryEntry extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'visitor_id', 'name', 'phone', 'email', 'is_winner', 'won_at', 'winner_rank', 'prize', 'entry_token', 'extra_fields'];

    protected $casts =
        [
            'is_winner' => 'boolean',
            'won_at' => 'datetime',
            'winner_rank' => 'integer',
            'extra_fields' => 'array',
        ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * The name as the vidiwall shows it: in full, or as the first name plus the
     * initial of the last one ("Anna K.").
     */
    public function screenName(string $format = LotteryScreen::NAME_FULL): string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', (string) $this->name));

        if ($format !== LotteryScreen::NAME_SHORT || ! str_contains($name, ' ')) {
            return $name;
        }

        $parts = explode(' ', $name);

        return $parts[0].' '.mb_strtoupper(mb_substr(end($parts), 0, 1)).'.';
    }

    /**
     * The last three digits of the phone number, enough to tell two winners
     * with the same name apart on the big screen.
     */
    public function maskedPhone(): ?string
    {
        $digits = (string) preg_replace('/\D/', '', (string) $this->phone);

        return strlen($digits) >= 3 ? '••• '.substr($digits, -3) : null;
    }
}
