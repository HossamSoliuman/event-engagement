<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\QuizAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function status(string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $round = $event->activeQuizRound();

        if (!$round) {
            $lastRound = $event->quizRounds()->where('status', 'finished')->latest()->first();

            return response()->json([
                'status'   => $lastRound ? 'finished' : 'waiting',
                'round_id' => $lastRound?->id,
            ]);
        }

        $questions = $round->questions()->get()->map(fn($q) => [
            'id'                  => $q->id,
            'question_text'       => $q->question_text,
            'options'             => $q->options,
            'time_limit_seconds'  => $round->time_limit_seconds,
        ]);

        return response()->json([
            'status'              => 'active',
            'round_id'            => $round->id,
            'time_limit_seconds'  => $round->time_limit_seconds,
            'questions_per_round' => $round->questions_per_round,
            'started_at'          => $round->started_at?->toIso8601String(),
            'questions'           => $questions,
        ]);
    }

    public function answer(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $round = $event->activeQuizRound();

        if (!$round) {
            return response()->json(['error' => 'No active quiz round.'], 422);
        }

        $data = $request->validate([
            'quiz_question_id' => 'required|integer|exists:quiz_questions,id',
            'selected_option'  => 'required|integer|min:0|max:3',
            'time_taken_ms'    => 'required|integer|min:0',
            'session_token'    => 'required|string|max:64',
            'guest_name'       => 'required|string|max:100',
        ]);

        $alreadyAnswered = QuizAnswer::where('quiz_round_id', $round->id)
            ->where('quiz_question_id', $data['quiz_question_id'])
            ->where('session_token', $data['session_token'])
            ->exists();

        if ($alreadyAnswered) {
            return response()->json(['error' => 'Already answered.'], 422);
        }

        $question  = $round->questions()->where('quiz_questions.id', $data['quiz_question_id'])->firstOrFail();
        $isCorrect = (int) $data['selected_option'] === (int) $question->correct_option;

        QuizAnswer::create([
            'quiz_round_id'    => $round->id,
            'quiz_question_id' => $data['quiz_question_id'],
            'event_id'         => $event->id,
            'session_token'    => $data['session_token'],
            'guest_name'       => $data['guest_name'],
            'selected_option'  => $data['selected_option'],
            'is_correct'       => $isCorrect,
            'answered_at'      => now(),
            'time_taken_ms'    => $data['time_taken_ms'],
        ]);

        return response()->json([
            'correct'         => $isCorrect,
            'correct_option'  => $question->correct_option,
            'correct_text'    => $question->options[$question->correct_option],
        ]);
    }

    public function results(string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $round = $event->quizRounds()->where('status', 'finished')->latest()->first();

        if (!$round) {
            return response()->json(['status' => 'no_results']);
        }

        $leaderboard = $round->getLeaderboard()->take(5)->values()->map(fn($row, $i) => [
            'rank'          => $i + 1,
            'guest_name'    => $row->guest_name,
            'correct_count' => (int) $row->correct_count,
            'total_ms'      => (int) $row->total_ms,
        ]);

        return response()->json([
            'status'      => 'finished',
            'round_id'    => $round->id,
            'leaderboard' => $leaderboard,
            'winner'      => $leaderboard->first(),
        ]);
    }
}
