<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\QuizQuestion;
use App\Models\QuizRound;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuizAdminController extends Controller
{
    public function index(Event $event)
    {
        $questions = $event->quizQuestions()->latest()->get();
        $rounds = $event->quizRounds()->latest()->with('questions')->withCount('answers')->get();
        $activeRound = $event->activeQuizRound();

        return view('admin.quiz.index', compact('event', 'questions', 'rounds', 'activeRound'));
    }

    public function storeQuestion(Request $request, Event $event)
    {
        $data = $request->validate([
            'question_text' => 'required|string|max:500',
            'options' => 'required|array|size:4',
            'options.*' => 'required|string|max:200',
            'correct_option' => 'required|integer|min:0|max:3',
            'sponsor_logo' => 'nullable|image|max:2048',
        ]);

        unset($data['sponsor_logo']);
        $data['event_id'] = $event->id;

        if ($request->hasFile('sponsor_logo')) {
            $data['sponsor_logo_path'] = $request->file('sponsor_logo')->store('quiz-sponsors', 'public');
        }

        QuizQuestion::create($data);

        ActivityLog::record('quiz.question_added', ['event' => $event->name], $event->id);

        return back()->with('success', 'Question added.');
    }

    public function updateQuestion(Request $request, QuizQuestion $question)
    {
        $data = $request->validate([
            'question_text' => 'required|string|max:500',
            'options' => 'required|array|size:4',
            'options.*' => 'required|string|max:200',
            'correct_option' => 'required|integer|min:0|max:3',
            'sponsor_logo' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        unset($data['sponsor_logo']);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('sponsor_logo')) {
            if ($question->sponsor_logo_path) {
                Storage::disk('public')->delete($question->sponsor_logo_path);
            }
            $data['sponsor_logo_path'] = $request->file('sponsor_logo')->store('quiz-sponsors', 'public');
        } elseif ($request->boolean('sponsor_logo_clear')) {
            if ($question->sponsor_logo_path) {
                Storage::disk('public')->delete($question->sponsor_logo_path);
            }
            $data['sponsor_logo_path'] = null;
        }

        $question->update($data);

        return back()->with('success', 'Question updated.');
    }

    public function destroyQuestion(QuizQuestion $question)
    {
        if ($question->sponsor_logo_path) {
            Storage::disk('public')->delete($question->sponsor_logo_path);
        }

        $question->delete();

        return back()->with('success', 'Question removed.');
    }

    public function updateSettings(Request $request, Event $event)
    {
        $data = $request->validate([
            'quiz_winner_text' => 'nullable|string|max:500',
            'quiz_end_sponsor_logo' => 'nullable|image|max:2048',
        ]);

        $update = ['quiz_winner_text' => $data['quiz_winner_text'] ?? null];

        if ($request->hasFile('quiz_end_sponsor_logo')) {
            if ($event->quiz_end_sponsor_logo_path) {
                Storage::disk('public')->delete($event->quiz_end_sponsor_logo_path);
            }
            $update['quiz_end_sponsor_logo_path'] = $request->file('quiz_end_sponsor_logo')->store('quiz-sponsors', 'public');
        } elseif ($request->boolean('quiz_end_sponsor_logo_clear')) {
            if ($event->quiz_end_sponsor_logo_path) {
                Storage::disk('public')->delete($event->quiz_end_sponsor_logo_path);
            }
            $update['quiz_end_sponsor_logo_path'] = null;
        }

        $event->update($update);

        return back()->with('success', 'Quiz end screen updated.');
    }

    public function startRound(Request $request, Event $event)
    {
        $data = $request->validate([
            'time_limit_seconds' => 'required|integer|min:5|max:300',
            'questions_per_round' => 'required|integer|min:1|max:10',
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer|exists:quiz_questions,id',
        ]);

        if ($event->activeQuizRound()) {
            return back()->with('error', 'A round is already active. End it first.');
        }

        $questionIds = array_values(array_unique($data['question_ids']));

        if (count($questionIds) < $data['questions_per_round']) {
            return back()->with('error', 'The pool has fewer questions than each guest should answer.');
        }

        $round = QuizRound::create([
            'event_id' => $event->id,
            'status' => 'active',
            'time_limit_seconds' => $data['time_limit_seconds'],
            'questions_per_round' => $data['questions_per_round'],
            'started_at' => now(),
        ]);

        $pivot = [];
        foreach ($questionIds as $order => $qid) {
            $pivot[$qid] = ['sort_order' => $order];
        }
        $round->questions()->sync($pivot);

        ActivityLog::record('quiz.round_started', ['round_id' => $round->id], $event->id);

        return back()->with('success', 'Quiz round started!');
    }

    public function endRound(Event $event)
    {
        $round = $event->activeQuizRound();

        if (! $round) {
            return back()->with('error', 'No active round found.');
        }

        $round->update(['status' => 'finished', 'finished_at' => now()]);
        ActivityLog::record('quiz.round_ended', ['round_id' => $round->id], $event->id);

        return back()->with('success', 'Round ended.');
    }

    public function leaderboard(Event $event, QuizRound $round)
    {
        $leaderboard = $round->getLeaderboard();
        $winner = $leaderboard->first();
        $questions = $round->questions;

        return view('admin.quiz.leaderboard', compact('event', 'round', 'leaderboard', 'winner', 'questions'));
    }

    public function resetRound(QuizRound $round)
    {
        $round->answers()->delete();
        $round->update(['status' => 'waiting', 'started_at' => null, 'finished_at' => null]);

        return back()->with('success', 'Round reset.');
    }

    public function export(Event $event)
    {
        $rounds = $event->quizRounds()->with(['answers', 'questions'])->get();

        $csv = "Round ID,Status,Started At,Question,Guest Name,Session Token,Selected Option,Correct,Time (ms)\n";

        foreach ($rounds as $round) {
            foreach ($round->answers as $answer) {
                $question = $round->questions->firstWhere('id', $answer->quiz_question_id);
                $csv .= implode(',', [
                    $round->id,
                    '"'.$round->status.'"',
                    '"'.($round->started_at?->format('Y-m-d H:i') ?? '').'"',
                    '"'.addslashes($question?->question_text ?? '').'"',
                    '"'.addslashes($answer->guest_name).'"',
                    '"'.$answer->session_token.'"',
                    $answer->selected_option,
                    $answer->is_correct ? 'YES' : 'No',
                    $answer->time_taken_ms,
                ])."\n";
            }
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"quiz-{$event->slug}.csv\"",
        ]);
    }
}
