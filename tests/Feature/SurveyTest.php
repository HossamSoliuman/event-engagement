<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackPageView;
use App\Models\Event;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SurveyTest extends TestCase
{
    use DatabaseTransactions;

    private function event(array $overrides = []): Event
    {
        return Event::factory()->create(array_merge(['module_survey' => true], $overrides));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function choiceQuestion(Event $event, array $overrides = []): SurveyQuestion
    {
        return SurveyQuestion::factory()->for($event)->create(array_merge([
            'question' => 'How was the atmosphere?',
            'options' => ['Great', 'Okay', 'Bad'],
        ], $overrides));
    }

    private function textQuestion(Event $event, array $overrides = []): SurveyQuestion
    {
        return SurveyQuestion::factory()->for($event)->text()->create(array_merge([
            'question' => 'What should we improve?',
        ], $overrides));
    }

    /**
     * @param  array<int, string>  $answers
     */
    private function submit(Event $event, array $answers, string $visitorId = 'visitor-a')
    {
        return $this->withCredentials()
            ->withCookie(TrackPageView::COOKIE, $visitorId)
            ->postJson(route('survey.guest.submit', $event->slug), ['answers' => $answers]);
    }

    public function test_fan_can_submit_choice_and_text_answers(): void
    {
        $event = $this->event();
        $choice = $this->choiceQuestion($event);
        $text = $this->textQuestion($event);

        $this->submit($event, [$choice->id => 'Great', $text->id => '  More food stands  '])
            ->assertOk()
            ->assertJson(['success' => true]);

        $response = SurveyResponse::where('event_id', $event->id)->sole();
        $this->assertSame('visitor-a', $response->visitor_id);
        $this->assertSame(
            [$choice->id => 'Great', $text->id => 'More food stands'],
            $response->answers()->pluck('answer', 'survey_question_id')->all(),
        );
    }

    public function test_required_question_must_be_answered(): void
    {
        $event = $this->event();
        $choice = $this->choiceQuestion($event);
        $this->textQuestion($event);

        $this->submit($event, [$choice->id => 'Great'])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Please answer: What should we improve?']);

        $this->assertSame(0, SurveyResponse::where('event_id', $event->id)->count());
    }

    public function test_choice_answer_must_be_one_of_the_options(): void
    {
        $event = $this->event();
        $choice = $this->choiceQuestion($event);

        $this->submit($event, [$choice->id => 'Amazing'])->assertStatus(422);

        $this->assertSame(0, SurveyResponse::where('event_id', $event->id)->count());
    }

    public function test_optional_questions_can_be_skipped_but_not_all_of_them(): void
    {
        $event = $this->event();
        $choice = $this->choiceQuestion($event, ['is_required' => false]);
        $text = $this->textQuestion($event, ['is_required' => false]);

        $this->submit($event, [$choice->id => '', $text->id => '   '], 'visitor-empty')
            ->assertStatus(422)
            ->assertJson(['message' => 'Please answer at least one question.']);

        $this->submit($event, [$choice->id => 'Okay'], 'visitor-partial')->assertOk();

        $response = SurveyResponse::where('event_id', $event->id)->sole();
        $this->assertSame(1, $response->answers()->count());
    }

    public function test_answers_that_are_too_long_or_not_strings_are_rejected(): void
    {
        $event = $this->event();
        $text = $this->textQuestion($event);

        $this->submit($event, [$text->id => str_repeat('a', SurveyQuestion::MAX_ANSWER_LENGTH + 1)])
            ->assertStatus(422)
            ->assertJson(['message' => 'Answers can be at most '.SurveyQuestion::MAX_ANSWER_LENGTH.' characters.']);

        $this->withCredentials()
            ->withCookie(TrackPageView::COOKIE, 'visitor-b')
            ->postJson(route('survey.guest.submit', $event->slug), ['answers' => [$text->id => ['nested']]])
            ->assertStatus(422);

        $this->assertSame(0, SurveyResponse::where('event_id', $event->id)->count());
    }

    public function test_a_visitor_can_only_answer_once(): void
    {
        $event = $this->event();
        $choice = $this->choiceQuestion($event);

        $this->submit($event, [$choice->id => 'Great'])->assertOk();
        $this->submit($event, [$choice->id => 'Bad'])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->submit($event, [$choice->id => 'Bad'], 'visitor-b')->assertOk();

        $this->assertSame(2, SurveyResponse::where('event_id', $event->id)->count());
    }

    public function test_answers_to_another_events_questions_are_ignored(): void
    {
        $event = $this->event();
        $choice = $this->choiceQuestion($event);
        $foreign = $this->textQuestion($this->event());

        $this->submit($event, [$choice->id => 'Great', $foreign->id => 'sneaky'])->assertOk();

        $this->assertSame(0, SurveyAnswer::where('survey_question_id', $foreign->id)->count());
        $this->assertSame(1, SurveyAnswer::where('survey_question_id', $choice->id)->count());
    }

    public function test_survey_cannot_be_answered_when_switched_off_or_empty(): void
    {
        $disabled = $this->event(['module_survey' => false]);
        $question = $this->choiceQuestion($disabled);

        $this->submit($disabled, [$question->id => 'Great'])->assertNotFound();

        $empty = $this->event();
        $this->submit($empty, [1 => 'Great'])
            ->assertStatus(422)
            ->assertJson(['message' => 'This survey has no questions yet.']);
    }

    public function test_landing_page_shows_the_survey_and_remembers_who_answered(): void
    {
        $event = $this->event();
        $choice = $this->choiceQuestion($event);

        $this->withCookie(TrackPageView::COOKIE, 'visitor-a')
            ->get("/e/{$event->slug}")
            ->assertOk()
            ->assertSee('id="screen-survey"', false)
            ->assertSee('How was the atmosphere?')
            ->assertViewHas('surveyAnswered', false);

        $this->submit($event, [$choice->id => 'Great'])->assertOk();

        $this->withCookie(TrackPageView::COOKIE, 'visitor-a')
            ->get("/e/{$event->slug}")
            ->assertViewHas('surveyAnswered', true);
    }

    public function test_landing_page_hides_the_survey_when_switched_off(): void
    {
        $event = $this->event(['module_survey' => false]);
        $this->choiceQuestion($event);

        $this->get("/e/{$event->slug}")
            ->assertOk()
            ->assertDontSee('id="screen-survey"', false);
    }

    public function test_admin_can_add_edit_and_delete_questions(): void
    {
        $admin = $this->admin();
        $event = $this->event();

        $this->actingAs($admin)
            ->post(route('admin.survey.questions.store', $event), [
                'question' => 'Favourite moment?',
                'type' => 'choice',
                'options' => ['Start', ' ', 'Finish', 'Podium', ''],
                'is_required' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $choice = $event->surveyQuestions()->sole();
        $this->assertSame(['Start', 'Finish', 'Podium'], $choice->options);
        $this->assertTrue($choice->is_required);

        $this->actingAs($admin)
            ->post(route('admin.survey.questions.store', $event), [
                'question' => 'Anything else?',
                'type' => 'text',
                'options' => ['ignored', 'also ignored'],
                'is_required' => '0',
            ])
            ->assertSessionHasNoErrors();

        $text = $event->surveyQuestions()->where('type', 'text')->sole();
        $this->assertNull($text->options);
        $this->assertFalse($text->is_required);

        $this->actingAs($admin)
            ->put(route('admin.survey.questions.update', [$event, $choice]), [
                'question' => 'Best moment?',
                'type' => 'choice',
                'options' => ['Start', 'Finish'],
                'is_required' => '0',
            ])
            ->assertSessionHasNoErrors();

        $choice->refresh();
        $this->assertSame('Best moment?', $choice->question);
        $this->assertSame(['Start', 'Finish'], $choice->options);
        $this->assertFalse($choice->is_required);

        $this->submit($event, [$choice->id => 'Start'])->assertOk();

        $this->actingAs($admin)
            ->delete(route('admin.survey.questions.destroy', [$event, $choice]))
            ->assertRedirect();

        $this->assertModelMissing($choice);
        $this->assertSame(0, SurveyAnswer::where('survey_question_id', $choice->id)->count());
    }

    public function test_choice_question_needs_two_to_twenty_distinct_options(): void
    {
        $admin = $this->admin();
        $event = $this->event();
        $store = fn (array $options) => $this->actingAs($admin)->post(route('admin.survey.questions.store', $event), [
            'question' => 'Pick one',
            'type' => 'choice',
            'options' => $options,
        ]);

        $store(['Only one', '', ''])->assertSessionHasErrors('options');
        $store(['Same', 'same'])->assertSessionHasErrors('options');
        $store(array_map('strval', range(1, SurveyQuestion::MAX_OPTIONS + 1)))->assertSessionHasErrors('options');
        $store(['Yes', 'No'])->assertSessionHasNoErrors();
        $store(array_map(fn (int $number) => "Option {$number}", range(1, SurveyQuestion::MAX_OPTIONS)))->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('admin.survey.questions.store', $event), ['question' => '', 'type' => 'poll'])
            ->assertSessionHasErrors(['question', 'type']);

        $this->assertSame(2, $event->surveyQuestions()->count());
        $this->assertCount(SurveyQuestion::MAX_OPTIONS, $event->surveyQuestions()->get()->last()->options);
    }

    public function test_admin_results_page_shows_tallies_and_text_answers(): void
    {
        $event = $this->event();
        $choice = $this->choiceQuestion($event);
        $text = $this->textQuestion($event, ['is_required' => false]);

        $this->submit($event, [$choice->id => 'Great', $text->id => 'Shorter queues'], 'v1')->assertOk();
        $this->submit($event, [$choice->id => 'Great'], 'v2')->assertOk();
        $this->submit($event, [$choice->id => 'Bad'], 'v3')->assertOk();

        $this->actingAs($this->admin())
            ->get(route('admin.survey.index', $event))
            ->assertOk()
            ->assertSee('How was the atmosphere?')
            ->assertSee('2 · 66.7%')
            ->assertSee('0 · 0%')
            ->assertSee('1 · 33.3%')
            ->assertSee('Shorter queues');
    }

    public function test_responses_export_has_one_row_per_fan_and_neutralises_formulas(): void
    {
        $event = $this->event();
        $choice = $this->choiceQuestion($event);
        $text = $this->textQuestion($event, ['question' => 'Comments, "quoted"?', 'is_required' => false]);

        $this->submit($event, [$choice->id => 'Great', $text->id => '=HYPERLINK("http://evil")'], 'v1')->assertOk();
        $this->submit($event, [$choice->id => 'Okay'], 'v2')->assertOk();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.survey.export', $event))
            ->assertOk()
            ->assertHeader('Content-Disposition', "attachment; filename=survey-responses-{$event->slug}.csv");

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);

        $rows = array_map('str_getcsv', explode("\n", trim(substr($csv, 3))));
        $this->assertSame(['Response #', 'Submitted At', 'How was the atmosphere?', 'Comments, "quoted"?'], $rows[0]);
        $this->assertSame(['1', 'Great', '\'=HYPERLINK("http://evil")'], [$rows[1][0], $rows[1][2], $rows[1][3]]);
        $this->assertSame(['2', 'Okay', ''], [$rows[2][0], $rows[2][2], $rows[2][3]]);
        $this->assertCount(3, $rows);
    }

    public function test_summary_export_lists_counts_and_percentages(): void
    {
        $event = $this->event();
        $choice = $this->choiceQuestion($event);
        $text = $this->textQuestion($event, ['is_required' => false]);

        $this->submit($event, [$choice->id => 'Great', $text->id => 'Loved it'], 'v1')->assertOk();
        $this->submit($event, [$choice->id => 'Bad', $text->id => 'Loved it'], 'v2')->assertOk();

        $csv = $this->actingAs($this->admin())
            ->get(route('admin.survey.export', [$event, 'type' => 'summary']))
            ->assertOk()
            ->streamedContent();

        $rows = array_map('str_getcsv', explode("\n", trim(substr($csv, 3))));

        $this->assertSame(['Total responses', '2'], $rows[0]);
        $this->assertContains(['How was the atmosphere?', 'One answer', 'Great', '1', '50%'], $rows);
        $this->assertContains(['How was the atmosphere?', 'One answer', 'Okay', '0', '0%'], $rows);
        $this->assertContains(['What should we improve?', 'Free text', 'Loved it', '2', '100%'], $rows);
    }

    public function test_admin_can_clear_all_responses(): void
    {
        $event = $this->event();
        $choice = $this->choiceQuestion($event);
        $this->submit($event, [$choice->id => 'Great'])->assertOk();

        $this->actingAs($this->admin())
            ->post(route('admin.survey.reset', $event))
            ->assertRedirect();

        $this->assertSame(0, SurveyResponse::where('event_id', $event->id)->count());
        $this->assertSame(0, SurveyAnswer::where('survey_question_id', $choice->id)->count());
        $this->assertModelExists($choice);

        $this->submit($event, [$choice->id => 'Okay'])->assertOk();
    }

    public function test_assigned_moderator_can_run_the_survey_but_others_cannot(): void
    {
        $event = $this->event();
        $moderator = User::factory()->create(['role' => 'moderator']);
        $event->moderators()->attach($moderator);
        $outsider = User::factory()->create(['role' => 'moderator']);

        $this->actingAs($moderator)
            ->post(route('moderator.survey.questions.store', $event), [
                'question' => 'Will you come back?',
                'type' => 'choice',
                'options' => ['Yes', 'Maybe', 'No'],
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($moderator)
            ->get(route('moderator.survey.index', $event))
            ->assertOk()
            ->assertSee('Will you come back?');

        $this->actingAs($moderator)->get(route('moderator.survey.export', $event))->assertOk();

        $this->actingAs($outsider)->get(route('moderator.survey.index', $event))->assertForbidden();
        $this->actingAs($outsider)->get(route('moderator.survey.export', $event))->assertForbidden();
    }

    public function test_a_question_cannot_be_changed_through_another_events_url(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $ownEvent = $this->event();
        $ownEvent->moderators()->attach($moderator);
        $foreignQuestion = $this->choiceQuestion($this->event());

        $this->actingAs($moderator)
            ->delete(route('moderator.survey.questions.destroy', [$ownEvent, $foreignQuestion]))
            ->assertNotFound();

        $this->actingAs($moderator)
            ->put(route('moderator.survey.questions.update', [$ownEvent, $foreignQuestion]), [
                'question' => 'Hijacked',
                'type' => 'text',
            ])
            ->assertNotFound();

        $this->assertSame('How was the atmosphere?', $foreignQuestion->fresh()->question);
    }

    public function test_add_form_stays_blank_and_any_number_of_questions_can_be_added(): void
    {
        $admin = $this->admin();
        $event = $this->event();

        foreach (range(1, 25) as $number) {
            $this->actingAs($admin)
                ->post(route('admin.survey.questions.store', $event), [
                    '_form' => 'add',
                    'question' => "Question {$number}?",
                    'type' => 'choice',
                    'options' => ['Yes', 'No'],
                ])
                ->assertRedirect()
                ->assertSessionHasNoErrors()
                ->assertSessionHas('success', "Question {$number} added.")
                ->assertSessionHas('sv_continue', true);
        }

        $this->assertSame(25, $event->surveyQuestions()->count());
        $this->assertSame(range(1, 25), $event->surveyQuestions()->pluck('position')->all());

        $page = $this->actingAs($admin)->get(route('admin.survey.index', $event))->assertOk();

        $this->assertSame(25, substr_count($page->getContent(), 'name="_method" value="PUT"'));
        $this->assertSame(1, substr_count($page->getContent(), 'name="_form" value="add"'));
        $this->assertMatchesRegularExpression('/id="sv-question-add"[^>]*value=""/', $page->getContent());
        $page->assertSee("querySelector('#sv-add .sv-question-input')?.focus", false);
    }

    public function test_admin_can_create_and_switch_a_several_answers_question(): void
    {
        $admin = $this->admin();
        $event = $this->event();

        $this->actingAs($admin)
            ->post(route('admin.survey.questions.store', $event), [
                'question' => 'What did you enjoy?',
                'type' => 'multiple',
                'options' => ['Food', 'Music', 'Show'],
            ])
            ->assertSessionHasNoErrors();

        $question = $event->surveyQuestions()->sole();
        $this->assertSame(SurveyQuestion::TYPE_CHOICE, $question->type);
        $this->assertTrue($question->allowsMultiple());
        $this->assertSame('Several answers', $question->answerTypeLabel());

        $update = fn (string $type) => $this->actingAs($admin)
            ->put(route('admin.survey.questions.update', [$event, $question]), [
                'question' => 'What did you enjoy?',
                'type' => $type,
                'options' => ['Food', 'Music', 'Show'],
            ])
            ->assertSessionHasNoErrors();

        $update('choice');
        $this->assertFalse($question->refresh()->allowsMultiple());
        $this->assertSame('One answer', $question->answerTypeLabel());

        $update('text');
        $question->refresh();
        $this->assertSame(SurveyQuestion::TYPE_TEXT, $question->type);
        $this->assertFalse($question->allow_multiple);
        $this->assertNull($question->options);
    }

    public function test_fan_can_pick_several_answers_on_a_several_answers_question(): void
    {
        $event = $this->event();
        $multiple = SurveyQuestion::factory()->for($event)->multiple()->create([
            'question' => 'What did you enjoy?',
            'options' => ['Food', 'Music', 'Show'],
        ]);

        $this->submit($event, [$multiple->id => ['Show', 'Food', 'Show']])->assertOk();

        $this->assertSame(
            ['Food', 'Show'],
            SurveyAnswer::where('survey_question_id', $multiple->id)->orderBy('id')->pluck('answer')->all(),
        );

        $this->submit($event, [$multiple->id => ['Food', 'Fireworks']], 'visitor-b')
            ->assertStatus(422)
            ->assertJson(['message' => 'Please pick one of the options for: What did you enjoy?']);

        $this->submit($event, [$multiple->id => []], 'visitor-c')
            ->assertStatus(422)
            ->assertJson(['message' => 'Please answer: What did you enjoy?']);

        $this->submit($event, [$multiple->id => 'Music'], 'visitor-d')->assertOk();

        $this->assertSame(2, SurveyResponse::where('event_id', $event->id)->count());
    }

    public function test_one_answer_question_rejects_several_picks(): void
    {
        $event = $this->event();
        $choice = $this->choiceQuestion($event);

        $this->submit($event, [$choice->id => ['Great', 'Okay']])->assertStatus(422);
        $this->submit($event, [$choice->id => ['Great', ['nested']]])->assertStatus(422);

        $this->assertSame(0, SurveyResponse::where('event_id', $event->id)->count());
    }

    public function test_several_answers_results_and_exports_count_fans(): void
    {
        $event = $this->event();
        $multiple = SurveyQuestion::factory()->for($event)->multiple()->create([
            'question' => 'What did you enjoy?',
            'options' => ['Food', 'Music', 'Show'],
        ]);

        $this->submit($event, [$multiple->id => ['Food', 'Music']], 'v1')->assertOk();
        $this->submit($event, [$multiple->id => ['Food']], 'v2')->assertOk();

        $this->actingAs($this->admin())
            ->get(route('admin.survey.index', $event))
            ->assertOk()
            ->assertSee('2 fans')
            ->assertSee('2 · 100%')
            ->assertSee('1 · 50%')
            ->assertSee('0 · 0%');

        $responses = $this->actingAs($this->admin())->get(route('admin.survey.export', $event))->streamedContent();
        $rows = array_map('str_getcsv', explode("\n", trim(substr($responses, 3))));
        $this->assertSame(['Food; Music', 'Food'], [$rows[1][2], $rows[2][2]]);

        $summary = $this->actingAs($this->admin())->get(route('admin.survey.export', [$event, 'type' => 'summary']))->streamedContent();
        $rows = array_map('str_getcsv', explode("\n", trim(substr($summary, 3))));
        $this->assertContains(['What did you enjoy?', 'Several answers', 'Food', '2', '100%'], $rows);
        $this->assertContains(['What did you enjoy?', 'Several answers', 'Music', '1', '50%'], $rows);
    }

    public function test_guest_page_marks_several_answers_questions(): void
    {
        $event = $this->event();
        SurveyQuestion::factory()->for($event)->multiple()->create(['question' => 'What did you enjoy?']);

        $this->get("/e/{$event->slug}")
            ->assertOk()
            ->assertSee('data-multiple="1"', false)
            ->assertSee('Pick all that apply');
    }

    public function test_admin_can_reorder_questions_and_fans_see_the_new_order(): void
    {
        $admin = $this->admin();
        $event = $this->event();
        $first = $this->choiceQuestion($event, ['question' => 'First?', 'position' => 1]);
        $second = $this->choiceQuestion($event, ['question' => 'Second?', 'position' => 2]);
        $third = $this->choiceQuestion($event, ['question' => 'Third?', 'position' => 3]);

        $this->actingAs($admin)
            ->putJson(route('admin.survey.questions.reorder', $event), ['order' => [$third->id, $first->id, $second->id]])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame([$third->id, $first->id, $second->id], $event->surveyQuestions()->pluck('id')->all());
        $this->assertSame([1, 2, 3], $event->surveyQuestions()->pluck('position')->all());

        $this->actingAs($admin)
            ->post(route('admin.survey.questions.store', $event), ['question' => 'Fourth?', 'type' => 'text'])
            ->assertSessionHasNoErrors();

        $this->get("/e/{$event->slug}")->assertSeeInOrder(['Third?', 'First?', 'Second?', 'Fourth?']);

        $this->actingAs($admin)
            ->get(route('admin.survey.index', $event))
            ->assertSeeInOrder(['data-id="'.$third->id.'"', 'data-id="'.$first->id.'"', 'data-id="'.$second->id.'"'], false);
    }

    public function test_reorder_must_list_exactly_the_events_questions(): void
    {
        $admin = $this->admin();
        $event = $this->event();
        $first = $this->choiceQuestion($event, ['position' => 1]);
        $second = $this->choiceQuestion($event, ['position' => 2]);
        $foreign = $this->choiceQuestion($this->event());
        $reorder = fn (array $order) => $this->actingAs($admin)
            ->putJson(route('admin.survey.questions.reorder', $event), ['order' => $order]);

        $reorder([$second->id])
            ->assertStatus(422)
            ->assertJson(['message' => 'The question list changed in the meantime. Reload the page and try again.']);
        $reorder([$second->id, $first->id, $foreign->id])->assertStatus(422);
        $reorder([$second->id, $foreign->id])->assertStatus(422);
        $reorder([$second->id, $second->id])->assertStatus(422);
        $reorder(['abc', $first->id])->assertStatus(422);
        $reorder([])->assertStatus(422);

        $this->assertSame([$first->id, $second->id], $event->surveyQuestions()->pluck('id')->all());
        $this->assertSame(0, $foreign->fresh()->position);
    }

    public function test_assigned_moderator_can_reorder_but_others_cannot(): void
    {
        $event = $this->event();
        $moderator = User::factory()->create(['role' => 'moderator']);
        $event->moderators()->attach($moderator);
        $outsider = User::factory()->create(['role' => 'moderator']);
        $first = $this->choiceQuestion($event, ['position' => 1]);
        $second = $this->choiceQuestion($event, ['position' => 2]);

        $this->actingAs($outsider)
            ->putJson(route('moderator.survey.questions.reorder', $event), ['order' => [$second->id, $first->id]])
            ->assertForbidden();

        $this->assertSame([$first->id, $second->id], $event->surveyQuestions()->pluck('id')->all());

        $this->actingAs($moderator)
            ->putJson(route('moderator.survey.questions.reorder', $event), ['order' => [$second->id, $first->id]])
            ->assertOk();

        $this->assertSame([$second->id, $first->id], $event->surveyQuestions()->pluck('id')->all());
    }

    public function test_failed_question_form_returns_to_the_form_and_keeps_the_input(): void
    {
        $admin = $this->admin();
        $event = $this->event();
        $question = $this->choiceQuestion($event);
        $index = route('admin.survey.index', $event);

        $this->actingAs($admin)
            ->from($index)
            ->post(route('admin.survey.questions.store', $event), [
                '_form' => 'add',
                'question' => 'Pick your favourites',
                'type' => 'multiple',
                'options' => ['Only one'],
            ])
            ->assertRedirect($index.'#sv-add')
            ->assertSessionHasErrors('options');

        $this->actingAs($admin)
            ->get($index)
            ->assertSee('value="Pick your favourites"', false)
            ->assertSee('value="Only one"', false);

        $this->actingAs($admin)
            ->from($index)
            ->put(route('admin.survey.questions.update', [$event, $question]), [
                '_form' => 'edit-'.$question->id,
                'question' => '',
                'type' => 'choice',
                'options' => ['Yes', 'No'],
            ])
            ->assertRedirect($index.'#sv-q-'.$question->id)
            ->assertSessionHasErrors('question');
    }

    public function test_module_can_be_toggled_and_titled_from_the_event_admin(): void
    {
        $admin = $this->admin();
        $event = $this->event(['module_survey' => false]);

        $this->actingAs($admin)
            ->postJson(route('admin.events.toggle-module', $event), ['module' => 'survey'])
            ->assertOk()
            ->assertJson(['enabled' => true]);

        $this->actingAs($admin)
            ->get(route('admin.events.show', $event))
            ->assertOk()
            ->assertSee(route('admin.survey.index', $event));
    }
}
