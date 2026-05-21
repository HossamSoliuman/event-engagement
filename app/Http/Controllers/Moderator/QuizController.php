<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\QuizQuestion;
use App\Models\QuizRound;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index(Event $event)
    {
        $questions   = $event->quizQuestions()->latest()->get();
        $rounds      = $event->quizRounds()->latest()->with('questions')->withCount('answers')->get();
        $activeRound = $event->activeQuizRound();

        return view('moderator.quiz.index', compact('event', 'questions', 'rounds', 'activeRound'));
    }

    public function storeQuestion(Request $request, Event $event)
    {
        $data = $request->validate([
            'question_text'  => 'required|string|max:500',
            'options'        => 'required|array|size:4',
            'options.*'      => 'required|string|max:200',
            'correct_option' => 'required|integer|min:0|max:3',
        ]);

        $data['event_id'] = $event->id;
        QuizQuestion::create($data);

        ActivityLog::record('quiz.question_added', ['event' => $event->name], $event->id);

        return back()->with('success', 'Question added.');
    }

    public function updateQuestion(Request $request, Event $event, QuizQuestion $question)
    {
        $data = $request->validate([
            'question_text'  => 'required|string|max:500',
            'options'        => 'required|array|size:4',
            'options.*'      => 'required|string|max:200',
            'correct_option' => 'required|integer|min:0|max:3',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $question->update($data);

        return back()->with('success', 'Question updated.');
    }

    public function destroyQuestion(Event $event, QuizQuestion $question)
    {
        $question->delete();

        return back()->with('success', 'Question removed.');
    }

    public function startRound(Request $request, Event $event)
    {
        $data = $request->validate([
            'time_limit_seconds'  => 'required|integer|min:5|max:300',
            'questions_per_round' => 'required|integer|min:1|max:10',
            'question_ids'        => 'required|array|min:1',
            'question_ids.*'      => 'integer|exists:quiz_questions,id',
        ]);

        if ($event->activeQuizRound()) {
            return back()->with('error', 'A round is already active. End it first.');
        }

        $questionIds = $data['question_ids'];

        if (count($questionIds) < $data['questions_per_round']) {
            return back()->with('error', 'Not enough questions selected for the round.');
        }

        $round = QuizRound::create([
            'event_id'            => $event->id,
            'status'              => 'active',
            'time_limit_seconds'  => $data['time_limit_seconds'],
            'questions_per_round' => $data['questions_per_round'],
            'started_at'          => now(),
        ]);

        $pivot = [];
        foreach (array_slice($questionIds, 0, $data['questions_per_round']) as $order => $qid) {
            $pivot[$qid] = ['sort_order' => $order];
        }
        $round->questions()->sync($pivot);

        ActivityLog::record('quiz.round_started', ['round_id' => $round->id], $event->id);

        return back()->with('success', 'Quiz round started!');
    }

    public function endRound(Event $event)
    {
        $round = $event->activeQuizRound();

        if (!$round) {
            return back()->with('error', 'No active round found.');
        }

        $round->update(['status' => 'finished', 'finished_at' => now()]);
        ActivityLog::record('quiz.round_ended', ['round_id' => $round->id], $event->id);

        return back()->with('success', 'Round ended.');
    }

    public function leaderboard(Event $event, QuizRound $round)
    {
        $leaderboard = $round->getLeaderboard();
        $winner      = $leaderboard->first();
        $questions   = $round->questions;

        return view('moderator.quiz.leaderboard', compact('event', 'round', 'leaderboard', 'winner', 'questions'));
    }

    public function resetRound(Event $event, QuizRound $round)
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
                    '"' . $round->status . '"',
                    '"' . ($round->started_at?->format('Y-m-d H:i') ?? '') . '"',
                    '"' . addslashes($question?->question_text ?? '') . '"',
                    '"' . addslashes($answer->guest_name) . '"',
                    '"' . $answer->session_token . '"',
                    $answer->selected_option,
                    $answer->is_correct ? 'YES' : 'No',
                    $answer->time_taken_ms,
                ]) . "\n";
            }
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"quiz-{$event->slug}.csv\"",
        ]);
    }
}
