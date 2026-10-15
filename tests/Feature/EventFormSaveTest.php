<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventFormSaveTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->event = Event::factory()->create();
    }

    public function test_saving_stays_on_the_tab_the_admin_was_editing(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $this->event), $this->payload(['_tab' => 'vidiwall']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.events.edit', $this->event).'#vidiwall')
            ->assertSessionHas('success', 'Event updated!');
    }

    public function test_saving_without_a_tab_returns_to_the_details_tab(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $this->event), $this->payload())
            ->assertRedirect(route('admin.events.edit', $this->event).'#details');
    }

    public function test_an_unknown_tab_falls_back_to_the_details_tab(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.events.update', $this->event), $this->payload(['_tab' => '"><script>alert(1)</script>']))
            ->assertRedirect(route('admin.events.edit', $this->event).'#details');
    }

    public function test_a_failed_save_reopens_the_same_tab(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.events.edit', $this->event))
            ->followingRedirects()
            ->put(route('admin.events.update', $this->event), $this->payload(['name' => '', '_tab' => 'modules']))
            ->assertOk()
            ->assertSee('type="hidden" name="_tab" value="modules"', false);
    }

    public function test_the_edit_form_starts_on_the_details_tab(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.events.edit', $this->event))
            ->assertOk()
            ->assertSee('type="hidden" name="_tab" value="details"', false);
    }

    public function test_the_create_form_has_no_tab_field(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.events.create'))
            ->assertOk()
            ->assertDontSee('type="hidden" name="_tab"', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => $this->event->name,
            'is_active' => 1,
            'design' => Event::landingDesignDefaults(),
        ], $overrides);
    }
}
