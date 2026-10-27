<?php

namespace Tests\Feature;

use App\Models\ArchivedFotoUpload;
use App\Models\Event;
use App\Models\FotoUpload;
use App\Models\User;
use App\Models\VidiwallPlayer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FotoArchiveTest extends TestCase
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

    public function test_archiving_moves_every_upload_out_of_the_queue(): void
    {
        $pending = $this->upload('pending');
        $approved = $this->upload('approved');
        $rejected = $this->upload('rejected');
        $otherEvent = Event::factory()->create();
        $otherUpload = $this->upload('approved', $otherEvent);

        $this->actingAs($this->admin)
            ->from(route('admin.fotos.archived', $this->event))
            ->post(route('admin.fotos.archive', $this->event))
            ->assertRedirect(route('admin.fotos.archived', $this->event))
            ->assertSessionHas('success', 'Archived 3 uploads. The Foto Queue starts fresh.');

        $this->assertSame(0, $this->event->fotoUploads()->count());
        $this->assertSame(
            [$pending->id, $approved->id, $rejected->id],
            $this->event->archivedFotoUploads()->orderBy('id')->pluck('id')->all()
        );

        $archived = ArchivedFotoUpload::query()->findOrFail($approved->id);
        $this->assertSame($approved->file_path, $archived->file_path);
        $this->assertSame('approved', $archived->status);
        $this->assertSame($approved->created_at->toDateTimeString(), $archived->created_at->toDateTimeString());
        $this->assertNotNull($archived->archived_at);

        $this->assertTrue(FotoUpload::query()->whereKey($otherUpload->id)->exists());
    }

    public function test_archiving_stops_the_vidiwall(): void
    {
        $this->upload('approved');
        $this->event->player()->play();
        $this->assertTrue($this->event->vidiwallPlayer->fresh()->isPlaying());

        $this->actingAs($this->admin)->post(route('admin.fotos.archive', $this->event))->assertRedirect();

        $player = VidiwallPlayer::query()->where('event_id', $this->event->id)->firstOrFail();
        $this->assertTrue($player->isStopped());
        $this->assertNull($player->foto_upload_id);
    }

    public function test_archiving_an_empty_queue_changes_nothing(): void
    {
        $this->event->player()->setQrScreen(true);
        $revision = $this->event->vidiwallPlayer->fresh()->revision;

        $this->actingAs($this->admin)
            ->post(route('admin.fotos.archive', $this->event))
            ->assertRedirect()
            ->assertSessionHas('error', 'There is nothing in the Foto Queue to archive.');

        $this->assertSame(0, $this->event->archivedFotoUploads()->count());

        $player = $this->event->vidiwallPlayer->fresh();
        $this->assertTrue($player->qr_screen, 'The QR screen stays up.');
        $this->assertSame($revision, $player->revision);
    }

    public function test_archived_uploads_keep_their_status(): void
    {
        $pending = $this->upload('pending');
        $approved = $this->upload('approved');
        $rejected = $this->upload('rejected');

        $this->assertSame(3, $this->event->archiveFotoUploads());

        $statuses = ArchivedFotoUpload::query()->pluck('status', 'id');
        $this->assertSame('pending', $statuses[$pending->id]);
        $this->assertSame('approved', $statuses[$approved->id]);
        $this->assertSame('rejected', $statuses[$rejected->id]);
    }

    public function test_a_second_archive_finds_nothing_left(): void
    {
        $this->upload('approved');

        $this->assertSame(1, $this->event->archiveFotoUploads());
        $this->assertSame(0, $this->event->archiveFotoUploads());
        $this->assertSame(1, $this->event->archivedFotoUploads()->count());
    }

    public function test_an_id_already_in_the_archive_fails_cleanly(): void
    {
        $upload = $this->upload('approved');
        ArchivedFotoUpload::factory()->create(['id' => $upload->id, 'event_id' => $this->event->id]);
        $this->event->player()->play();

        $this->actingAs($this->admin)
            ->post(route('admin.fotos.archive', $this->event))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue(FotoUpload::query()->whereKey($upload->id)->exists(), 'Nothing left the queue.');
        $this->assertTrue($this->event->vidiwallPlayer->fresh()->isPlaying(), 'The vidiwall kept playing.');
    }

    public function test_the_tab_counts_include_the_archive(): void
    {
        $this->upload('pending');
        ArchivedFotoUpload::factory()->count(2)->create(['event_id' => $this->event->id]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.fotos.index', [$this->event, 'status' => 'pending']))
            ->assertOk()
            ->assertJsonPath('counts.pending', 1)
            ->assertJsonPath('counts.archived', 2);
    }

    public function test_a_page_past_the_end_goes_to_the_last_page(): void
    {
        ArchivedFotoUpload::factory()->create(['event_id' => $this->event->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.fotos.archived', [$this->event, 'page' => 5]))
            ->assertRedirect(route('admin.fotos.archived', [$this->event, 'page' => 1]));
    }

    public function test_each_archive_run_shows_its_full_total(): void
    {
        ArchivedFotoUpload::factory()->count(61)->create(['event_id' => $this->event->id, 'archived_at' => now()->subDay()->startOfMinute()]);

        $this->actingAs($this->admin)
            ->get(route('admin.fotos.archived', $this->event))
            ->assertOk()
            ->assertSee('61 uploads');
    }

    public function test_export_and_download_all_include_archived_uploads(): void
    {
        Storage::fake('media');
        $current = $this->upload('approved');
        $archived = ArchivedFotoUpload::factory()->create(['event_id' => $this->event->id, 'uploader_name' => 'Day One Dana', 'status' => 'rejected']);
        Storage::disk('media')->put($current->file_path, 'current');
        Storage::disk('media')->put($archived->file_path, 'archived');

        $csv = $this->actingAs($this->admin)->get(route('admin.fotos.export', $this->event))->assertOk()->getContent();
        $this->assertStringContainsString('Archived At', $csv);
        $this->assertStringContainsString('"Day One Dana",,photo,,rejected,No', $csv);

        $response = $this->actingAs($this->admin)->get(route('admin.fotos.download-all', $this->event))->assertOk();
        $zipPath = $response->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive;
        $zip->open($zipPath);
        $this->assertNotFalse($zip->locateName(basename($current->file_path)));
        $this->assertNotFalse($zip->locateName(basename($archived->file_path)));
        $zip->close();
        File::delete($zipPath);
    }

    public function test_media_downloads_and_statistics_include_archived_uploads(): void
    {
        $this->upload('approved');
        ArchivedFotoUpload::factory()->count(2)->create(['event_id' => $this->event->id, 'created_at' => now()]);

        $this->actingAs($this->admin)
            ->get(route('admin.media-downloads.index'))
            ->assertOk()
            ->assertSee($this->event->name.' — 3 uploads');

        $stats = $this->actingAs($this->admin)
            ->get(route('admin.statistics.index', $this->event))
            ->assertOk()
            ->viewData('stats');

        $this->assertSame(3, $stats['fotos']['total']);
    }

    public function test_the_archived_tab_lists_earlier_days(): void
    {
        $upload = $this->upload('approved');
        $upload->update(['uploader_name' => 'Archived Annie']);
        $this->event->archiveFotoUploads();
        $this->upload('pending');

        $this->actingAs($this->admin)
            ->get(route('admin.fotos.archived', $this->event))
            ->assertOk()
            ->assertSee('Archived Annie')
            ->assertSeeText('1 upload in the Foto Queue')
            ->assertSee('Archive all');

        $this->actingAs($this->admin)
            ->get(route('admin.fotos.index', $this->event))
            ->assertOk()
            ->assertSee(route('admin.fotos.archived', $this->event))
            ->assertDontSee('Archived Annie');
    }

    public function test_the_archived_tab_is_empty_before_the_first_archive(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.fotos.archived', $this->event))
            ->assertOk()
            ->assertSee('Nothing archived yet')
            ->assertSee('nothing to archive yet');
    }

    public function test_a_moderator_can_archive_their_event(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->event->moderators()->attach($moderator);
        $this->upload('pending');

        $this->actingAs($moderator)->get(route('moderator.fotos.archived', $this->event))->assertOk();
        $this->actingAs($moderator)->post(route('moderator.fotos.archive', $this->event))->assertRedirect();

        $this->assertSame(1, $this->event->archivedFotoUploads()->count());
    }

    public function test_a_moderator_cannot_archive_another_event(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->upload('pending');

        $this->actingAs($moderator)->post(route('moderator.fotos.archive', $this->event))->assertForbidden();

        $this->assertSame(1, $this->event->fotoUploads()->count());
    }

    public function test_restoring_one_upload_puts_it_back_with_its_status(): void
    {
        $current = $this->upload('approved');
        $current->update(['display_order' => 4]);
        $approved = ArchivedFotoUpload::factory()->create(['event_id' => $this->event->id, 'status' => 'approved', 'display_order' => 1, 'displayed_at' => now()]);
        $rejected = ArchivedFotoUpload::factory()->create(['event_id' => $this->event->id, 'status' => 'rejected', 'admin_note' => 'Blurry']);

        $this->actingAs($this->admin)
            ->postJson(route('admin.fotos.restore-archived', [$this->event, $approved]))
            ->assertOk()
            ->assertJsonPath('message', 'Restored to Approved.')
            ->assertJsonPath('counts.approved', 2)
            ->assertJsonPath('counts.archived', 1);

        $restored = FotoUpload::query()->findOrFail($approved->id);
        $this->assertSame('approved', $restored->status);
        $this->assertSame($approved->file_path, $restored->file_path);
        $this->assertNotNull($restored->displayed_at);
        $this->assertGreaterThan(4, $restored->display_order, 'It joins the end of the playlist.');
        $this->assertSame([$current->id, $approved->id], $this->event->playlist()->pluck('id')->all());
        $this->assertFalse(ArchivedFotoUpload::query()->whereKey($approved->id)->exists());

        $this->actingAs($this->admin)
            ->post(route('admin.fotos.restore-archived', [$this->event, $rejected]))
            ->assertRedirect()
            ->assertSessionHas('success', 'Restored to Rejected.');

        $this->assertSame('rejected', FotoUpload::query()->findOrFail($rejected->id)->status);
        $this->assertSame('Blurry', FotoUpload::query()->findOrFail($rejected->id)->admin_note);
    }

    public function test_restore_all_keeps_each_runs_playlist_order(): void
    {
        $dayOneFirst = ArchivedFotoUpload::factory()->create(['event_id' => $this->event->id, 'display_order' => 1, 'archived_at' => now()->subDays(2)->startOfMinute()]);
        $dayOneSecond = ArchivedFotoUpload::factory()->create(['event_id' => $this->event->id, 'display_order' => 2, 'archived_at' => now()->subDays(2)->startOfMinute()]);
        $dayTwoFirst = ArchivedFotoUpload::factory()->create(['event_id' => $this->event->id, 'display_order' => 1, 'archived_at' => now()->subDay()->startOfMinute()]);
        $pending = ArchivedFotoUpload::factory()->create(['event_id' => $this->event->id, 'status' => 'pending', 'approved_at' => null, 'archived_at' => now()->subDay()->startOfMinute()]);
        $current = $this->upload('approved');

        $this->actingAs($this->admin)
            ->post(route('admin.fotos.restore-archive', $this->event))
            ->assertRedirect()
            ->assertSessionHas('success', 'Restored 4 uploads to the Foto Queue.');

        $this->assertSame(0, $this->event->archivedFotoUploads()->count());
        $this->assertSame(
            [$current->id, $dayOneFirst->id, $dayOneSecond->id, $dayTwoFirst->id],
            $this->event->playlist()->pluck('id')->all()
        );
        $this->assertSame('pending', FotoUpload::query()->findOrFail($pending->id)->status);
    }

    public function test_restore_all_with_an_empty_archive_says_so(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.fotos.restore-archive', $this->event))
            ->assertRedirect()
            ->assertSessionHas('error', 'The archive is empty, there is nothing to restore.');
    }

    public function test_an_upload_from_another_event_cannot_be_restored_here(): void
    {
        $otherEvent = Event::factory()->create();
        $archived = ArchivedFotoUpload::factory()->create(['event_id' => $otherEvent->id]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.fotos.restore-archived', [$this->event, $archived]))
            ->assertNotFound();

        $this->assertTrue(ArchivedFotoUpload::query()->whereKey($archived->id)->exists());
    }

    public function test_a_restore_that_clashes_with_a_queue_id_changes_nothing(): void
    {
        $upload = $this->upload('approved');
        $archived = ArchivedFotoUpload::factory()->create(['id' => $upload->id, 'event_id' => $this->event->id]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.fotos.restore-archived', [$this->event, $archived]))
            ->assertStatus(409);

        $this->assertTrue(ArchivedFotoUpload::query()->whereKey($archived->id)->exists());
    }

    public function test_a_moderator_can_restore_on_their_event_only(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->event->moderators()->attach($moderator);
        $archived = ArchivedFotoUpload::factory()->create(['event_id' => $this->event->id]);
        $otherEvent = Event::factory()->create();
        $otherArchived = ArchivedFotoUpload::factory()->create(['event_id' => $otherEvent->id]);

        $this->actingAs($moderator)
            ->postJson(route('moderator.fotos.restore-archived', [$this->event, $archived]))
            ->assertOk();

        $this->actingAs($moderator)
            ->post(route('moderator.fotos.restore-archive', $otherEvent))
            ->assertForbidden();

        $this->assertTrue(FotoUpload::query()->whereKey($archived->id)->exists());
        $this->assertTrue(ArchivedFotoUpload::query()->whereKey($otherArchived->id)->exists());
    }

    public function test_the_archived_tab_shows_the_restore_buttons(): void
    {
        ArchivedFotoUpload::factory()->create(['event_id' => $this->event->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.fotos.archived', $this->event))
            ->assertOk()
            ->assertSee('data-act="restore"', false)
            ->assertSee('Restore all')
            ->assertSee('<kbd>R</kbd>', false);
    }

    public function test_the_archive_buttons_ask_in_the_app_dialog_not_the_browser(): void
    {
        $this->upload('approved');
        ArchivedFotoUpload::factory()->create(['event_id' => $this->event->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.fotos.archived', $this->event))
            ->assertOk()
            ->assertSee('id="appDialog"', false)
            ->assertSee('data-confirm="Archive all 1 upload?"', false)
            ->assertSee('data-confirm="Restore all 1 archived upload?"', false)
            ->assertDontSee('confirm(', false);
    }

    public function test_pruning_keeps_archived_files(): void
    {
        Storage::fake('media');
        $upload = $this->upload('approved');
        Storage::disk('media')->put($upload->file_path, 'photo');
        $this->event->archiveFotoUploads();

        $this->artisan('media:prune', ['--min-age' => 0])->assertSuccessful();

        Storage::disk('media')->assertExists($upload->file_path);
    }

    private function upload(string $status, ?Event $event = null): FotoUpload
    {
        $event ??= $this->event;

        return FotoUpload::query()->create([
            'event_id' => $event->id,
            'file_path' => 'fotos/event-'.$event->id.'/'.uniqid('upload-', true).'.jpg',
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
