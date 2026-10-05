<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyQuestion extends Model
{
    use HasFactory;

    public const TYPE_CHOICE = 'choice';

    public const TYPE_TEXT = 'text';

    public const MIN_OPTIONS = 2;

    public const MAX_OPTIONS = 6;

    public const MAX_ANSWER_LENGTH = 1000;

    protected $fillable = [
        'event_id',
        'question',
        'type',
        'options',
        'is_required',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }

    public function isChoice(): bool
    {
        return $this->type === self::TYPE_CHOICE;
    }

    /**
     * @return list<string>
     */
    public function optionList(): array
    {
        return $this->isChoice() ? array_values($this->options ?? []) : [];
    }
}
