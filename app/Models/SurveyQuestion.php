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

    /** Admin form alias for a choice question where fans may pick several options. */
    public const TYPE_MULTIPLE = 'multiple';

    /** The answer types the admin form offers, with the label shown for each. */
    public const ANSWER_TYPES = [
        self::TYPE_CHOICE => 'One answer',
        self::TYPE_MULTIPLE => 'Several answers',
        self::TYPE_TEXT => 'Free text',
    ];

    public const MIN_OPTIONS = 2;

    public const MAX_OPTIONS = 20;

    public const MAX_ANSWER_LENGTH = 1000;

    protected $fillable = [
        'event_id',
        'position',
        'question',
        'type',
        'options',
        'allow_multiple',
        'is_required',
    ];

    protected $casts = [
        'position' => 'integer',
        'options' => 'array',
        'allow_multiple' => 'boolean',
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

    public function allowsMultiple(): bool
    {
        return $this->isChoice() && $this->allow_multiple;
    }

    /**
     * The admin form's answer type: a key of ANSWER_TYPES.
     */
    public function answerType(): string
    {
        if (! $this->isChoice()) {
            return self::TYPE_TEXT;
        }

        return $this->allow_multiple ? self::TYPE_MULTIPLE : self::TYPE_CHOICE;
    }

    public function answerTypeLabel(): string
    {
        return self::ANSWER_TYPES[$this->answerType()];
    }

    /**
     * @return list<string>
     */
    public function optionList(): array
    {
        return $this->isChoice() ? array_values($this->options ?? []) : [];
    }
}
