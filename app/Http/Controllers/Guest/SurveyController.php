<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Middleware\TrackPageView;
use App\Http\Requests\SubmitSurveyRequest;
use App\Models\ActivityLog;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SurveyController extends Controller
{
    public function submit(SubmitSurveyRequest $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->where('module_survey', true)->firstOrFail();
        $questions = $event->surveyQuestions()->get();

        if ($questions->isEmpty()) {
            return $this->reject('This survey has no questions yet.');
        }

        $visitorId = $request->cookie(TrackPageView::COOKIE);

        if ($visitorId && $event->surveyResponses()->where('visitor_id', $visitorId)->exists()) {
            return $this->reject('You have already answered this survey. Thank you!');
        }

        $submitted = $request->validated('answers');
        $answers = [];

        foreach ($questions as $question) {
            $submittedValue = $submitted[$question->id] ?? null;

            if (is_array($submittedValue) && ! $question->allowsMultiple()) {
                return $this->reject("Please answer: {$question->question}");
            }

            $values = collect((array) $submittedValue)
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn (string $value) => $value !== '')
                ->unique();

            if ($values->isEmpty()) {
                if ($question->is_required) {
                    return $this->reject("Please answer: {$question->question}");
                }

                continue;
            }

            if ($question->isChoice()) {
                if ($values->diff($question->optionList())->isNotEmpty()) {
                    return $this->reject("Please pick one of the options for: {$question->question}");
                }

                $values = collect($question->optionList())->intersect($values);
            }

            foreach ($values as $value) {
                $answers[] = ['survey_question_id' => $question->id, 'answer' => $value];
            }
        }

        if ($answers === []) {
            return $this->reject('Please answer at least one question.');
        }

        DB::transaction(function () use ($event, $visitorId, $answers) {
            $event->surveyResponses()
                ->create(['visitor_id' => $visitorId])
                ->answers()
                ->createMany($answers);
        });

        ActivityLog::record('survey.submitted', ['answers' => collect($answers)->pluck('survey_question_id')->unique()->count()], $event->id);

        return response()->json(['success' => true, 'message' => 'Thanks for your feedback!']);
    }

    private function reject(string $message): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], 422);
    }
}
