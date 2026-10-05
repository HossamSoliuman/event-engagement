<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSurveyQuestionRequest;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SurveyController extends Controller
{
    /** How many of the newest free-text answers the results panel previews per question. */
    private const TEXT_PREVIEW_LIMIT = 5;

    /** Leading characters that make Excel treat a cell as a formula. */
    private const FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    public function index(Event $event): View
    {
        $questions = $event->surveyQuestions()->get();

        return view($this->indexView(), [
            'event' => $event,
            'questions' => $questions,
            'results' => $this->results($questions),
            'latestTextAnswers' => $this->latestTextAnswers($questions),
            'responseCount' => $event->surveyResponses()->count(),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function storeQuestion(StoreSurveyQuestionRequest $request, Event $event): RedirectResponse
    {
        $event->surveyQuestions()->create($request->questionAttributes());

        ActivityLog::record('survey.question_added', ['event' => $event->name], $event->id);

        return back()->with('success', 'Question added.');
    }

    public function updateQuestion(StoreSurveyQuestionRequest $request, Event $event, SurveyQuestion $surveyQuestion): RedirectResponse
    {
        $surveyQuestion->update($request->questionAttributes());

        return back()->with('success', 'Question updated.');
    }

    public function destroyQuestion(Event $event, SurveyQuestion $surveyQuestion): RedirectResponse
    {
        $surveyQuestion->delete();

        return back()->with('success', 'Question removed.');
    }

    public function resetResponses(Event $event): RedirectResponse
    {
        $event->surveyResponses()->delete();

        ActivityLog::record('survey.reset', [], $event->id);

        return back()->with('success', 'All survey responses were cleared.');
    }

    public function export(Request $request, Event $event): StreamedResponse
    {
        return $request->query('type') === 'summary'
            ? $this->summaryCsv($event)
            : $this->responsesCsv($event);
    }

    protected function indexView(): string
    {
        return 'admin.survey.index';
    }

    protected function routePrefix(): string
    {
        return 'admin.';
    }

    /**
     * Answer tallies per question. Choice questions list every configured option
     * (even at zero) in their set order, followed by any value that is no longer
     * an option because the question was edited after fans answered.
     *
     * @param  Collection<int, SurveyQuestion>  $questions
     * @return array<int, array{answered: int, rows: list<array{label: string, count: int, percent: float}>}>
     */
    private function results(Collection $questions): array
    {
        $tallies = SurveyAnswer::query()
            ->whereIn('survey_question_id', $questions->pluck('id'))
            ->selectRaw('survey_question_id, answer, COUNT(*) as total')
            ->groupBy('survey_question_id', 'answer')
            ->get()
            ->groupBy('survey_question_id');

        return $questions->mapWithKeys(function (SurveyQuestion $question) use ($tallies) {
            $counts = $tallies->get($question->id, collect())
                ->mapWithKeys(fn (SurveyAnswer $row) => [$row->answer => (int) $row->total])
                ->sortDesc();

            if ($question->isChoice()) {
                $counts = collect($question->optionList())
                    ->mapWithKeys(fn (string $option) => [$option => $counts->get($option, 0)])
                    ->union($counts);
            }

            $answered = $counts->sum();

            return [$question->id => [
                'answered' => $answered,
                'rows' => $counts->map(fn (int $count, $label) => [
                    'label' => (string) $label,
                    'count' => $count,
                    'percent' => $answered > 0 ? round($count / $answered * 100, 1) : 0.0,
                ])->values()->all(),
            ]];
        })->all();
    }

    /**
     * @param  Collection<int, SurveyQuestion>  $questions
     * @return array<int, list<string>>
     */
    private function latestTextAnswers(Collection $questions): array
    {
        return $questions
            ->reject(fn (SurveyQuestion $question) => $question->isChoice())
            ->mapWithKeys(fn (SurveyQuestion $question) => [
                $question->id => $question->answers()->latest('id')->limit(self::TEXT_PREVIEW_LIMIT)->pluck('answer')->all(),
            ])
            ->all();
    }

    /**
     * One row per fan, one column per question — opens straight into Excel.
     */
    private function responsesCsv(Event $event): StreamedResponse
    {
        $questions = $event->surveyQuestions()->get();

        return $this->csvDownload("survey-responses-{$event->slug}.csv", function (callable $row) use ($event, $questions) {
            $row(array_merge(['Response #', 'Submitted At'], $questions->pluck('question')->all()));

            $number = 0;

            $event->surveyResponses()->with('answers')->orderBy('id')->chunk(500, function (Collection $responses) use ($row, $questions, &$number) {
                foreach ($responses as $response) {
                    /** @var SurveyResponse $response */
                    $answers = $response->answers->pluck('answer', 'survey_question_id');

                    $row(array_merge(
                        [++$number, $response->created_at->format('Y-m-d H:i')],
                        $questions->map(fn (SurveyQuestion $question) => $answers->get($question->id, ''))->all(),
                    ));
                }
            });
        });
    }

    /**
     * Counts and percentages per answer, for a client who wants the report rather than the raw rows.
     */
    private function summaryCsv(Event $event): StreamedResponse
    {
        $questions = $event->surveyQuestions()->get();
        $results = $this->results($questions);
        $responseCount = $event->surveyResponses()->count();

        return $this->csvDownload("survey-summary-{$event->slug}.csv", function (callable $row) use ($questions, $results, $responseCount) {
            $row(['Total responses', $responseCount]);
            $row([]);
            $row(['Question', 'Type', 'Answer', 'Count', 'Percent']);

            foreach ($questions as $question) {
                $type = $question->isChoice() ? 'Multiple choice' : 'Free text';
                $rows = $results[$question->id]['rows'];

                if ($rows === []) {
                    $row([$question->question, $type, '(no answers yet)', 0, '0%']);
                }

                foreach ($rows as $result) {
                    $row([$question->question, $type, $result['label'], $result['count'], $result['percent'].'%']);
                }
            }
        });
    }

    /**
     * Streams a UTF-8 CSV with a BOM so Excel keeps umlauts intact. The writer
     * hands the callback a row function that neutralises every cell against
     * formula injection, since answers are typed by the public.
     *
     * @param  callable(callable(array<int, mixed>): void): void  $write
     */
    private function csvDownload(string $filename, callable $write): StreamedResponse
    {
        return response()->streamDownload(function () use ($write) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            $write(function (array $cells) use ($out): void {
                fputcsv($out, array_map(fn (mixed $cell) => $this->csvCell($cell), $cells));
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvCell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return in_array($value[0], self::FORMULA_PREFIXES, true) ? "'".$value : $value;
    }
}
