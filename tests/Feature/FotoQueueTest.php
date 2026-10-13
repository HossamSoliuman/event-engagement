<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\FotoUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FotoQueueTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->event = Event::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_tab_comes_back_as_rendered_cards_newest_first(): void
    {
        $older = $this->upload('pending');
        $newer = $this->upload('pending');
        $this->upload('approved');

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.fotos.index', [$this->event, 'status' => 'pending']))
            ->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('has_more', false)
            ->assertJsonPath('counts.pending', 2)
            ->assertJsonPath('counts.approved', 1)
            ->assertJsonPath('cards.0.id', $newer->id)
            ->assertJsonPath('cards.1.id', $older->id);

        $this->assertIsInt($response->json('cursor'));
        $this->assertStringContainsString('data-act="approve"', $response->json('cards.0.html'));
        $this->assertStringContainsString('data-act="reject"', $response->json('cards.0.html'));
    }

    public function test_a_tab_pages_through_with_before(): void
    {
        $first = $this->upload('rejected');
        foreach (range(1, 40) as $ignored) {
            $this->upload('rejected');
        }

        $page = $this->actingAs($this->admin)
            ->getJson(route('admin.fotos.index', [$this->event, 'status' => 'rejected']))
            ->assertJsonCount(40, 'cards')
            ->assertJsonPath('has_more', true);

        $this->actingAs($this->admin)
            ->getJson(route('admin.fotos.index', [$this->event, 'status' => 'rejected', 'before' => $page->json('cards.39.id')]))
            ->assertJsonCount(1, 'cards')
            ->assertJsonPath('cards.0.id', $first->id)
            ->assertJsonPath('has_more', false);
    }

    public function test_an_unknown_tab_falls_back_to_pending(): void
    {
        $pending = $this->upload('pending');

        $this->actingAs($this->admin)
            ->getJson(route('admin.fotos.index', [$this->event, 'status' => 'bogus']))
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('cards.0.id', $pending->id);
    }

    public function test_approved_cards_offer_reject_and_delete_only(): void
    {
        $this->upload('approved');

        $html = $this->actingAs($this->admin)
            ->getJson(route('admin.fotos.index', [$this->event, 'status' => 'approved']))
            ->json('cards.0.html');

        $this->assertStringContainsString('data-act="reject"', $html);
        $this->assertStringContainsString('data-act="delete"', $html);
        $this->assertStringNotContainsString('data-act="approve"', $html);
        $this->assertStringNotContainsString('Push Live', $html);
    }

    public function test_actions_answer_json_with_fresh_counts(): void
    {
        Storage::fake('media');
        $foto = $this->upload('pending');

        $this->actingAs($this->admin)
            ->postJson(route('admin.fotos.approve', $foto))
            ->assertOk()
            ->assertJsonPath('counts', ['pending' => 0, 'approved' => 1, 'rejected' => 0]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.fotos.reject', $foto))
            ->assertOk()
            ->assertJsonPath('counts', ['pending' => 0, 'approved' => 0, 'rejected' => 1]);

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.fotos.destroy', $foto))
            ->assertOk()
            ->assertJsonPath('counts', ['pending' => 0, 'approved' => 0, 'rejected' => 0]);

        $this->assertModelMissing($foto);
    }

    public function test_actions_still_redirect_for_plain_form_posts(): void
    {
        $foto = $this->upload('pending');

        $this->actingAs($this->admin)
            ->post(route('admin.fotos.approve', $foto))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_moderator_actions_answer_json(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->event->moderators()->attach($moderator);
        $foto = $this->upload('approved');

        $this->actingAs($moderator)
            ->postJson(route('moderator.fotos.reject', [$this->event, $foto]))
            ->assertOk()
            ->assertJsonPath('counts.rejected', 1)
            ->assertJsonPath('counts.approved', 0);

        $this->actingAs($moderator)
            ->getJson(route('moderator.fotos.index', [$this->event, 'status' => 'rejected']))
            ->assertOk()
            ->assertJsonPath('cards.0.id', $foto->id);
    }

    public function test_status_sends_the_cards_of_the_open_tab_that_changed_since_the_cursor(): void
    {
        Carbon::setTestNow('2026-10-13 12:00:00');
        $old = $this->upload('pending');

        Carbon::setTestNow('2026-10-13 12:10:00');
        $fresh = $this->upload('pending');
        $this->upload('approved');

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.fotos.status', [$this->event, 'status' => 'pending', 'since' => now()->subSecond()->getTimestamp()]))
            ->assertOk()
            ->assertJsonCount(1, 'changed')
            ->assertJsonPath('changed.0.id', $fresh->id)
            ->assertJsonPath('cursor', now()->getTimestamp());

        $this->assertStringContainsString('data-id="'.$fresh->id.'"', $response->json('changed.0.html'));
        $this->assertNotContains($old->id, array_column($response->json('changed'), 'id'));
    }

    public function test_status_without_a_cursor_sends_no_cards(): void
    {
        $this->upload('pending');

        $this->actingAs($this->admin)
            ->getJson(route('admin.fotos.status', [$this->event, 'status' => 'pending']))
            ->assertOk()
            ->assertJsonMissingPath('changed');
    }

    public function test_page_renders_the_open_tab_without_the_auto_refresh(): void
    {
        $pending = $this->upload('pending');

        $this->actingAs($this->admin)
            ->get(route('admin.fotos.index', [$this->event, 'status' => 'pending']))
            ->assertOk()
            ->assertSee('data-id="'.$pending->id.'"', false)
            ->assertSee('data-fq-tab="approved"', false)
            ->assertDontSee('auto-refreshes');
    }

    private function upload(string $status): FotoUpload
    {
        return FotoUpload::query()->create([
            'event_id' => $this->event->id,
            'file_path' => 'fotos/event-'.$this->event->id.'/'.uniqid('upload-', true).'.jpg',
            'media_type' => 'photo',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'uploader_name' => 'Guest',
            'status' => $status,
            'display_order' => $status === 'approved' ? 1 : 0,
            'approved_at' => $status === 'approved' ? now() : null,
        ]);
    }
}
