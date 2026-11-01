<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModeratorAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_open_the_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.events.index'))
            ->assertOk();
    }

    public function test_moderator_opening_the_admin_panel_lands_on_their_own_event(): void
    {
        $event = Event::factory()->create();
        $moderator = User::factory()->create(['role' => 'moderator']);
        $event->moderators()->attach($moderator);

        $this->actingAs($moderator)
            ->get(route('admin.events.index'))
            ->assertRedirect(route('moderator.dashboard', $event));

        $this->actingAs($moderator)
            ->get(route('admin.events.edit', Event::factory()->create()))
            ->assertRedirect(route('moderator.dashboard', $event));
    }

    public function test_moderator_cannot_change_anything_in_the_admin_panel(): void
    {
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create(['name' => 'Other Client Event']);
        $moderator = User::factory()->create(['role' => 'moderator']);
        $event->moderators()->attach($moderator);

        $this->actingAs($moderator)
            ->post(route('admin.events.duplicate', $otherEvent))
            ->assertForbidden();

        $this->actingAs($moderator)
            ->put(route('admin.events.update', $otherEvent), ['name' => 'Hijacked'])
            ->assertForbidden();

        $this->assertSame('Other Client Event', $otherEvent->fresh()->name);
    }

    public function test_moderator_without_events_is_forbidden_from_the_admin_panel(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);

        $this->actingAs($moderator)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_moderator_cannot_open_another_events_moderator_portal(): void
    {
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $moderator = User::factory()->create(['role' => 'moderator']);
        $event->moderators()->attach($moderator);

        $this->actingAs($moderator)
            ->get(route('moderator.dashboard', $event))
            ->assertOk();

        $this->actingAs($moderator)
            ->get(route('moderator.dashboard', $otherEvent))
            ->assertForbidden();
    }

    public function test_moderator_logs_in_to_their_newest_event(): void
    {
        $olderEvent = Event::factory()->create();
        $newerEvent = Event::factory()->create();
        $moderator = User::factory()->create(['role' => 'moderator', 'password' => 'secret-pass']);
        $newerEvent->moderators()->attach($moderator);
        $olderEvent->moderators()->attach($moderator);

        $this->post(route('admin.login.post'), ['email' => $moderator->email, 'password' => 'secret-pass'])
            ->assertRedirect(route('moderator.dashboard', $newerEvent));
    }

    public function test_moderator_without_events_is_logged_out_at_login(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator', 'password' => 'secret-pass']);

        $this->post(route('admin.login.post'), ['email' => $moderator->email, 'password' => 'secret-pass'])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_moderator_with_several_events_can_switch_between_them(): void
    {
        $event = Event::factory()->create(['name' => 'Client Event One']);
        $secondEvent = Event::factory()->create(['name' => 'Client Event Two']);
        $foreignEvent = Event::factory()->create(['name' => 'Someone Elses Event']);
        $moderator = User::factory()->create(['role' => 'moderator']);
        $event->moderators()->attach($moderator);
        $secondEvent->moderators()->attach($moderator);

        $this->actingAs($moderator)
            ->get(route('moderator.dashboard', $event))
            ->assertOk()
            ->assertSee('id="eventSwitch"', false)
            ->assertSee(route('moderator.dashboard', $secondEvent), false)
            ->assertDontSee($foreignEvent->name);
    }

    public function test_moderator_with_one_event_sees_no_switcher(): void
    {
        $event = Event::factory()->create();
        $moderator = User::factory()->create(['role' => 'moderator']);
        $event->moderators()->attach($moderator);

        $this->actingAs($moderator)
            ->get(route('moderator.dashboard', $event))
            ->assertOk()
            ->assertDontSee('id="eventSwitch"', false);
    }

    public function test_mobile_api_is_for_admins_only(): void
    {
        $event = Event::factory()->create();
        $moderator = User::factory()->create(['role' => 'moderator', 'password' => 'secret-pass']);
        $event->moderators()->attach($moderator);

        $this->postJson('/api/v1/login', ['email' => $moderator->email, 'password' => 'secret-pass'])
            ->assertForbidden();

        Sanctum::actingAs($moderator);
        $this->getJson("/api/v1/events/{$event->id}/stats")->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $this->getJson("/api/v1/events/{$event->id}/stats")->assertOk();
    }
}
