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
            ->assertSee('Draw winner #2')
            ->assertDontSee('Draw the Winner!');

        $this->actingAs($moderator)
            ->from(route('moderator.lottery.index', $this->event))
            ->post(route('moderator.lottery.reset', $this->event))
            ->assertRedirect(route('moderator.lottery.index', $this->event));

        $this->assertFalse($this->event->fresh()->lottery_drawn);

        $this->actingAs($moderator)
            ->get(route('moderator.lottery.index', $this->event))
            ->assertOk()
            ->assertSee('Draw the Winner!');

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
            ->assertSee('Draw the Winner!');
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
        $winner = $event->latestLotteryWinner();
        $this->assertNotNull($winner);
        $this->assertSame($winner->id, $event->lottery_winner_id);
    }

    public function test_drawing_again_keeps_the_first_winner_and_adds_another(): void
    {
        $this->enter(3);
        $first = $this->event->drawLotteryWinner('Grand prize');

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.draw', $this->event), ['prize' => '  Signed helmet  ', 'next_rank' => 2])
            ->assertRedirect(route('admin.lottery.index', $this->event))
            ->assertSessionHas('winner_drawn');

        $winners = $this->event->lotteryWinners()->get();
        $this->assertCount(2, $winners);
        $this->assertSame([$first->id, 1, 'Grand prize'], [$winners[0]->id, $winners[0]->winner_rank, $winners[0]->prize]);
        $this->assertNotSame($first->id, $winners[1]->id);
        $this->assertSame([2, 'Signed helmet'], [$winners[1]->winner_rank, $winners[1]->prize]);
        $this->assertSame($winners[1]->id, $this->event->fresh()->lottery_winner_id);

        $this->actingAs($this->admin)
            ->get(route('admin.lottery.index', $this->event))
            ->assertOk()
            ->assertSeeInOrder([$winners[0]->name, 'Grand prize', $winners[1]->name, 'Signed helmet'])
            ->assertSee('Draw winner #3')
            ->assertSee('Reset Lottery');
    }

    public function test_a_draw_form_sent_twice_draws_only_once(): void
    {
        $this->enter(4);
        $this->event->drawLotteryWinner();

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.draw', $this->event), ['next_rank' => 2])
            ->assertSessionHas('winner_drawn');

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.draw', $this->event), ['next_rank' => 2])
            ->assertSessionHas('error', 'Winner #2 has already been drawn. Check the winners before drawing again.');

        $this->assertSame([1, 2], $this->event->lotteryWinners()->pluck('winner_rank')->all());
    }

    public function test_drawing_stops_once_everyone_has_won(): void
    {
        $this->enter(2);
        $this->event->drawLotteryWinner();
        $this->event->drawLotteryWinner();

        $this->actingAs($this->admin)
            ->get(route('admin.lottery.index', $this->event))
            ->assertOk()
            ->assertSee('Everyone has won')
            ->assertDontSee('id="drawForm"', false);

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.draw', $this->event))
            ->assertSessionHas('error', 'Everyone in the lottery has already won.');

        $this->assertSame(2, $this->event->lotteryWinners()->count());
    }

    public function test_every_entry_is_drawn_once_before_anyone_wins_twice(): void
    {
        $this->enter(5);

        $drawn = collect(range(1, 5))->map(fn () => $this->event->drawLotteryWinner()->id);

        $this->assertCount(5, $drawn->unique());
        $this->assertNull($this->event->drawLotteryWinner());
        $this->assertSame([1, 2, 3, 4, 5], $this->event->lotteryWinners()->pluck('winner_rank')->all());
    }

    public function test_redraw_replaces_a_winner_in_the_same_place_with_the_same_prize(): void
    {
        $this->enter(4);
        $first = $this->event->drawLotteryWinner('Grand prize');
        $second = $this->event->drawLotteryWinner('Scarf');

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.redraw', [$this->event, $first]), ['on_screen' => 0])
            ->assertRedirect(route('admin.lottery.index', $this->event))
            ->assertSessionHas('winner_drawn');

        $first->refresh();
        $this->assertFalse($first->is_winner);
        $this->assertNull($first->winner_rank);
        $this->assertNull($first->prize);

        $winners = $this->event->lotteryWinners()->get();
        $this->assertCount(2, $winners);
        $this->assertNotContains($first->id, $winners->pluck('id'));
        $this->assertSame([1, 'Grand prize'], [$winners[0]->winner_rank, $winners[0]->prize]);
        $this->assertSame([$second->id, 2], [$winners[1]->id, $winners[1]->winner_rank]);
    }

    public function test_redraw_with_no_one_left_keeps_the_winner(): void
    {
        $this->enter(1);
        $winner = $this->event->drawLotteryWinner();

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.redraw', [$this->event, $winner]))
            ->assertSessionHas('error');

        $this->assertTrue($winner->fresh()->is_winner);
    }

    public function test_putting_a_winner_back_moves_later_winners_up_and_keeps_the_prize_for_the_next_draw(): void
    {
        $this->enter(4);
        $first = $this->event->drawLotteryWinner('Grand prize');
        $second = $this->event->drawLotteryWinner();
        $third = $this->event->drawLotteryWinner();

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.release', [$this->event, $first]))
            ->assertRedirect(route('admin.lottery.index', $this->event))
            ->assertSessionHasInput('prize', 'Grand prize');

        $this->assertFalse($first->fresh()->is_winner);
        $this->assertSame([$second->id => 1, $third->id => 2], $this->event->lotteryWinners()->pluck('winner_rank', 'id')->all());
        $this->assertSame($third->id, $this->event->fresh()->lottery_winner_id);
    }

    public function test_deleting_a_winner_keeps_the_other_winners_and_renumbers_them(): void
    {
        $this->enter(3);
        $first = $this->event->drawLotteryWinner();
        $second = $this->event->drawLotteryWinner();

        $this->actingAs($this->admin)
            ->delete(route('admin.lottery.destroy', $first))
            ->assertSessionHas('success');

        $event = $this->event->fresh();
        $this->assertTrue($event->lottery_drawn);
        $this->assertSame([$second->id => 1], $event->lotteryWinners()->pluck('winner_rank', 'id')->all());
    }

    public function test_winner_actions_are_scoped_to_the_event(): void
    {
        $this->enter(2);
        $other = Event::factory()->create(['module_lottery' => true]);
        $outsider = LotteryEntry::factory()->for($other)->winner()->create();
        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->event->moderators()->attach($moderator);

        $this->actingAs($this->admin)
            ->post(route('admin.lottery.release', [$this->event, $outsider]))
            ->assertNotFound();

        $this->actingAs($moderator)
            ->post(route('moderator.lottery.redraw', [$this->event, $outsider]))
            ->assertNotFound();

        $this->actingAs($moderator)
            ->delete(route('moderator.lottery.destroy', [$this->event, $outsider]))
            ->assertNotFound();

        $this->assertTrue($outsider->fresh()->is_winner);
    }

    public function test_search_only_lists_entries_of_the_event(): void
    {
        $this->enter(1);
        $other = Event::factory()->create(['module_lottery' => true]);
        LotteryEntry::factory()->for($other)->create(['name' => 'Outsider Person', 'phone' => '+100000000123']);

        $this->actingAs($this->admin)
            ->get(route('admin.lottery.index', [$this->event, 'search' => '+1000']))
            ->assertOk()
            ->assertSee('Guest 1')
            ->assertDontSee('Outsider Person');
    }

    public function test_export_lists_winner_numbers_and_prizes(): void
    {
        $this->enter(2);
        $winner = $this->event->drawLotteryWinner('Ski "Pro" pass');

        $csv = $this->actingAs($this->admin)
            ->get(route('admin.lottery.export', $this->event))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('"Winner","Winner #","Prize"', $csv);
        $this->assertStringContainsString('"'.$winner->name.'","'.$winner->phone.'"', $csv);
        $this->assertStringContainsString('YES,1,"Ski ""Pro"" pass"', $csv);
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
