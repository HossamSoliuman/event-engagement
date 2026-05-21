<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAnswer extends Model
{
    protected $fillable = [
        'quiz_round_id',
        'quiz_question_id',
        'event_id',
        'session_token',
        'guest_name',
        'selected_option',
        'is_correct',
        'answered_at',
        'time_taken_ms',
    ];

    protected $casts = [
        'is_correct'      => 'boolean',
        'answered_at'     => 'datetime',
        'selected_option' => 'integer',
        'time_taken_ms'   => 'integer',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(QuizRound::class, 'quiz_round_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
