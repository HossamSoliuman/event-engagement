<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\LotteryEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class LotteryTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->event = Event::factory()->create(['module_lottery' => true]);
    }

    public function test_admin_can_reset_and_draw_again(): void
    {
        $this->enter(3);

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.draw', $this->event))
            ->assertRedirect(route('admin.lottery.index', $this->event));

        $this->assertTrue($this->event->fresh()->lottery_drawn);

        $this->actingAs($this->admin)
            ->get(route('admin.lottery.index', $this->event))
            ->assertOk()
            ->assertSee('Reset Lottery')
            ->assertSee(route('admin.lottery.reset', $this->event))
            ->assertDontSee('Draw the Winner!');

        $this->actingAs($this->admin)
            ->from(route('admin.lottery.index', $this->event))
            ->post(route('admin.lottery.reset', $this->event))
            ->assertRedirect(route('admin.lottery.index', $this->event));

        $event = $this->event->fresh();
        $this->assertFalse($event->lottery_drawn);
        $this->assertNull($event->lottery_winner_id);
        $this->assertSame(0, $event->lotteryEntries()->where('is_winner', true)->count());

        $this->actingAs($this->admin)
            ->get(route('admin.lottery.index', $this->event))
            ->assertOk()
            ->assertSee('Draw the Winner!');

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.draw', $this->event))
            ->assertSessionHas('winner_drawn');

        $this->assertTrue($this->event->fresh()->lottery_drawn);
        $this->assertSame(1, $this->event->lotteryEntries()->where('is_winner', true)->count());
    }

    public function test_moderator_can_reset_and_draw_again(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->event->moderators()->attach($moderator);
        $this->enter(3);

        $this->actingAs($moderator)
            ->post(route('moderator.lottery.draw', $this->event))
            ->assertRedirect(route('moderator.lottery.index', $this->event));

        $this->actingAs($moderator)
            ->get(route('moderator.lottery.index', $this->event))
            ->assertOk()
            ->assertSee('Reset Lottery')
            ->assertDontSee('Draw Winner');

        $this->actingAs($moderator)
            ->from(route('moderator.lottery.index', $this->event))
            ->post(route('moderator.lottery.reset', $this->event))
            ->assertRedirect(route('moderator.lottery.index', $this->event));

        $this->assertFalse($this->event->fresh()->lottery_drawn);

        $this->actingAs($moderator)
            ->get(route('moderator.lottery.index', $this->event))
            ->assertOk()
            ->assertSee('Draw Winner');

        $this->actingAs($moderator)
            ->post(route('moderator.lottery.draw', $this->event))
            ->assertSessionHas('winner_drawn');

        $this->assertSame(1, $this->event->lotteryEntries()->where('is_winner', true)->count());
    }

    public function test_deleting_the_winner_reopens_the_draw(): void
    {
        $this->enter(3);
        $winner = $this->event->drawLotteryWinner();

        $this->actingAs($this->admin)
            ->delete(route('admin.lottery.destroy', $winner))
            ->assertSessionHas('success');

        $event = $this->event->fresh();
        $this->assertFalse($event->lottery_drawn);
        $this->assertNull($event->lottery_winner_id);

        $this->actingAs($this->admin)
            ->get(route('admin.lottery.index', $this->event))
            ->assertOk()
            ->assertSee('Draw the Winner!')
            ->assertSee('function animateDraw', false);

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.draw', $this->event))
            ->assertSessionHas('winner_drawn');

        $this->assertSame(1, $this->event->lotteryEntries()->where('is_winner', true)->count());
    }

    public function test_moderator_deleting_the_winner_reopens_the_draw(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->event->moderators()->attach($moderator);
        $this->enter(2);
        $winner = $this->event->drawLotteryWinner();

        $this->actingAs($moderator)
            ->delete(route('moderator.lottery.destroy', [$this->event, $winner]))
            ->assertSessionHas('success');

        $this->assertFalse($this->event->fresh()->lottery_drawn);

        $this->actingAs($moderator)
            ->get(route('moderator.lottery.index', $this->event))
            ->assertOk()
            ->assertSee('Draw Winner');
    }

    public function test_a_drawn_flag_without_a_winner_entry_does_not_block_drawing(): void
    {
        $this->enter(2);
        $this->event->update(['lottery_drawn' => true, 'lottery_winner_id' => 999999]);

        $this->actingAs($this->admin)
            ->get(route('admin.lottery.index', $this->event))
            ->assertOk()
            ->assertSee('Draw the Winner!')
            ->assertSee('function animateDraw', false);

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.draw', $this->event))
            ->assertSessionHas('winner_drawn');

        $event = $this->event->fresh();
        $winner = $event->currentLotteryWinner();
        $this->assertNotNull($winner);
        $this->assertSame($winner->id, $event->lottery_winner_id);
    }

    public function test_drawing_twice_without_reset_keeps_the_first_winner(): void
    {
        $this->enter(3);
        $first = $this->event->drawLotteryWinner();

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.draw', $this->event))
            ->assertSessionHas('error');

        $this->assertSame($first->id, $this->event->currentLotteryWinner()->id);
        $this->assertSame(1, $this->event->lotteryEntries()->where('is_winner', true)->count());
    }

    public function test_drawing_with_no_entries_reports_it(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.lottery.draw', $this->event))
            ->assertSessionHas('error', 'No entries to draw from.');

        $this->assertFalse($this->event->fresh()->lottery_drawn);
    }

    private function enter(int $count): void
    {
        foreach (range(1, $count) as $i) {
            LotteryEntry::create([
                'event_id' => $this->event->id,
                'name' => "Guest {$i}",
                'phone' => '+100000000'.$i,
                'entry_token' => Str::random(32),
            ]);
        }
    }
}
