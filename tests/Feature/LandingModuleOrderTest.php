<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LandingModuleOrderTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_the_landing_page_uses_the_default_order_when_none_is_saved(): void
    {
        $event = Event::factory()->cleanStyle()->create();

        $this->assertSame(['fotobomb', 'voting', 'lottery', 'membership'], $this->cardOrder($this->get("/e/{$event->slug}")));
    }

    public function test_the_landing_page_follows_the_saved_order(): void
    {
        $event = Event::factory()->cleanStyle()->create([
            'landing_module_order' => ['membership', 'lottery', 'voting', 'fotobomb', 'quiz', 'fanclash', 'survey'],
        ]);

        $this->assertSame(['membership', 'lottery', 'voting', 'fotobomb'], $this->cardOrder($this->get("/e/{$event->slug}")));
    }

    public function test_modules_missing_from_the_saved_order_are_appended_in_the_default_order(): void
    {
        $event = Event::factory()->cleanStyle()->create([
            'module_quiz' => true,
            'landing_module_order' => ['lottery', 'fotobomb'],
        ]);

        $this->assertSame(['lottery', 'fotobomb', 'voting', 'membership', 'quiz'], $this->cardOrder($this->get("/e/{$event->slug}")));
    }

    public function test_unknown_and_disabled_modules_in_the_saved_order_are_ignored(): void
    {
        $event = Event::factory()->cleanStyle()->create([
            'landing_module_order' => ['ghost', 'quiz', 'membership', 42, 'membership'],
        ]);

        $this->assertSame(['membership', 'fotobomb', 'voting', 'lottery'], $this->cardOrder($this->get("/e/{$event->slug}")));
        $this->assertSame(
            ['quiz', 'membership', 'fotobomb', 'voting', 'lottery', 'fanclash', 'survey'],
            $event->landingModuleOrder(),
        );
    }

    public function test_admin_saves_the_module_order(): void
    {
        $event = Event::factory()->create();
        $order = ['survey', 'quiz', 'fanclash', 'membership', 'lottery', 'voting', 'fotobomb'];

        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $event), $this->payload($event, ['landing_module_order' => $order]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame($order, $event->fresh()->landing_module_order);
    }

    public function test_a_new_event_saves_its_module_order(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.events.store'), [
                'name' => 'Order Cup',
                'landing_module_order' => ['voting', 'fotobomb'],
            ])
            ->assertSessionHasNoErrors();

        $event = Event::query()->where('name', 'Order Cup')->firstOrFail();

        $this->assertSame(['voting', 'fotobomb', 'lottery', 'membership', 'quiz', 'fanclash', 'survey'], $event->landingModuleOrder());
    }

    public function test_saving_without_the_module_order_keeps_the_saved_one(): void
    {
        $order = ['lottery', 'fotobomb', 'voting', 'membership', 'quiz', 'fanclash', 'survey'];
        $event = Event::factory()->create(['landing_module_order' => $order]);

        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $event), $this->payload($event))
            ->assertSessionHasNoErrors();

        $this->assertSame($order, $event->fresh()->landing_module_order);
    }

    public function test_the_module_order_must_hold_known_modules_once(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $event), $this->payload($event, ['landing_module_order' => ['fotobomb', 'ghost']]))
            ->assertSessionHasErrors(['landing_module_order.1' => 'The module order contains an unknown module.']);

        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $event), $this->payload($event, ['landing_module_order' => ['voting', 'voting']]))
            ->assertSessionHasErrors(['landing_module_order.0' => 'Each module can appear only once in the module order.']);

        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $event), $this->payload($event, ['landing_module_order' => 'voting']))
            ->assertSessionHasErrors('landing_module_order');

        $this->assertNull($event->fresh()->landing_module_order);
    }

    public function test_the_edit_form_lists_every_module_in_the_saved_order(): void
    {
        $event = Event::factory()->create(['landing_module_order' => ['quiz', 'lottery']]);

        $response = $this->actingAs($this->admin)->get(route('admin.events.edit', $event))->assertOk();

        preg_match_all('/name="landing_module_order\[\]" value="(\w+)"/', $response->getContent(), $matches);
        $this->assertSame(['quiz', 'lottery', 'fotobomb', 'voting', 'membership', 'fanclash', 'survey'], $matches[1]);
    }

    public function test_the_create_form_lists_every_module_in_the_default_order(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.events.create'))->assertOk();

        preg_match_all('/name="landing_module_order\[\]" value="(\w+)"/', $response->getContent(), $matches);
        $this->assertSame(array_keys(Event::landingModules()), $matches[1]);
    }

    public function test_the_preview_reorders_the_module_grid_live(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.events.preview', $event))
            ->assertOk()
            ->assertSee('if (grid && Array.isArray(d.order))', false);
    }

    public function test_a_duplicate_keeps_the_module_order(): void
    {
        $order = ['membership', 'voting', 'fotobomb', 'lottery', 'quiz', 'fanclash', 'survey'];
        $event = Event::factory()->create(['landing_module_order' => $order]);

        $this->actingAs($this->admin)->post(route('admin.events.duplicate', $event));

        $copy = Event::query()->where('name', $event->name.' (Copy)')->firstOrFail();

        $this->assertSame($order, $copy->landing_module_order);
    }

    public function test_copy_is_offered_on_the_events_list_and_the_event_pages(): void
    {
        $event = Event::factory()->create();
        $action = 'action="'.route('admin.events.duplicate', $event).'"';

        $this->actingAs($this->admin)->get(route('admin.events.index'))->assertSee($action, false)->assertSee('Copy</button>', false);
        $this->actingAs($this->admin)->get(route('admin.events.show', $event))->assertSee($action, false);
        $this->actingAs($this->admin)->get(route('admin.events.edit', $event))->assertSee($action, false);
        $this->actingAs($this->admin)->get(route('admin.events.create'))->assertDontSee('/duplicate"', false);
    }

    public function test_duplicating_opens_the_copy_and_logs_it(): void
    {
        $event = Event::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('admin.events.duplicate', $event));

        $copy = Event::query()->where('name', $event->name.' (Copy)')->firstOrFail();

        $response->assertRedirect(route('admin.events.edit', $copy))
            ->assertSessionHas('success', 'Event copied. You are now editing the copy, which stays inactive until you switch it on.');
        $this->assertFalse($copy->is_active);
        $this->assertDatabaseHas('activity_log', ['event_id' => $copy->id, 'action' => 'event.copied']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Event $event, array $overrides = []): array
    {
        return array_merge([
            'name' => $event->name,
            'is_active' => 1,
            'design' => Event::landingDesignDefaults(),
        ], $overrides);
    }

    /**
     * @return list<string>
     */
    private function cardOrder(TestResponse $response): array
    {
        preg_match_all('/class="cl-card" data-mod="(\w+)"/', $response->assertOk()->getContent(), $matches);

        return $matches[1];
    }
}
