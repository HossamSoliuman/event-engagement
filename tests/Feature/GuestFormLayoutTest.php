<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\SurveyQuestion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GuestFormLayoutTest extends TestCase
{
    use DatabaseTransactions;

    public function test_send_buttons_come_before_the_privacy_consent_on_every_form(): void
    {
        $event = Event::factory()->create([
            'module_fotobomb' => true,
            'module_lottery' => true,
            'module_voting' => true,
            'module_membership' => true,
            'module_survey' => true,
            'voting_closed' => false,
            'voting_options' => [['name' => 'Team A'], ['name' => 'Team B']],
        ]);
        SurveyQuestion::factory()->for($event)->create();

        $response = $this->get("/e/{$event->slug}")->assertOk();

        $response->assertSeeInOrder(['id="lPhone"', 'onclick="submitLottery()"', 'id="gdpr-box-lottery"'], false);
        $response->assertSeeInOrder(['id="fotoName"', 'onclick="submitFoto()"', 'id="gdpr-box-foto"'], false);
        $response->assertSeeInOrder(['id="voteGrid"', 'onclick="submitVote()"', 'id="gdpr-box-vote"'], false);
        $response->assertSeeInOrder(['id="mEmail"', 'onclick="submitMembership()"', 'id="gdpr-box-member"'], false);
        $response->assertSeeInOrder(['onclick="submitSurvey()"', 'id="gdpr-box-survey"'], false);
    }

    public function test_consent_checkbox_is_still_rendered_for_each_form(): void
    {
        $event = Event::factory()->create([
            'module_lottery' => true,
            'privacy_policy_text' => 'I agree to the Privacy Policy of Loop One.',
        ]);

        $this->get("/e/{$event->slug}")
            ->assertOk()
            ->assertSee('id="gdpr-lottery"', false)
            ->assertSee('Loop One');
    }
}
