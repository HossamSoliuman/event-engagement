<?php

namespace App\Traits;

use App\Models\ActivityLog;
use App\Models\ArchivedFotoUpload;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The Archived tab of the Foto Queue for admins and moderators: the uploads
 * moved out by "Archive all", grouped by archive run, plus archiving and
 * restoring (one upload, or the whole archive).
 */
trait ServesFotoArchive
{
    protected function showFotoArchive(Request $request, Event $event, string $view): View|RedirectResponse
    {
        $archived = $event->archivedFotoUploads()
            ->orderByDesc('archived_at')
            ->orderByDesc('id')
            ->paginate(60);

        if ($archived->isEmpty() && $archived->currentPage() > 1) {
            return redirect($request->fullUrlWithQuery(['page' => $archived->lastPage()]));
        }

        $days = $archived->getCollection()->groupBy(fn (ArchivedFotoUpload $foto) => $foto->archived_at->toDateTimeString());

        return view($view, [
            'event' => $event,
            'archived' => $archived,
            'days' => $days,
            'dayTotals' => $event->archivedFotoUploads()
                ->whereIn('archived_at', $days->keys())
                ->selectRaw('archived_at, COUNT(*) as total')
                ->groupBy('archived_at')
                ->get()
                ->mapWithKeys(fn (ArchivedFotoUpload $day) => [$day->archived_at->toDateTimeString() => (int) $day->total]),
            'counts' => $event->fotoStatusCounts(),
        ]);
    }

    protected function archiveFotoQueue(Event $event): RedirectResponse
    {
        try {
            $archivedCount = $event->archiveFotoUploads();
        } catch (UniqueConstraintViolationException $exception) {
            report($exception);

            return back()->with('error', 'Some uploads have the same id as uploads already in the archive, so nothing was archived. Please contact support.');
        }

        if ($archivedCount === 0) {
            return back()->with('error', 'There is nothing in the Foto Queue to archive.');
        }

        ActivityLog::record('foto.archived', ['count' => $archivedCount], $event->id);

        return back()->with('success', "Archived {$archivedCount} ".str('upload')->plural($archivedCount).'. The Foto Queue starts fresh.');
    }

    /**
     * Puts one archived upload back into the Foto Queue. Answers the page
     * script with fresh tab counts, or redirects for plain form posts.
     */
    protected function restoreArchivedFoto(Request $request, Event $event, ArchivedFotoUpload $archivedFoto): JsonResponse|RedirectResponse
    {
        try {
            $event->restoreArchivedFotoUploads($archivedFoto);
        } catch (UniqueConstraintViolationException $exception) {
            report($exception);

            return $this->restoreFailed($request);
        }

        ActivityLog::record('foto.restored', ['foto_id' => $archivedFoto->id], $event->id);

        $message = 'Restored to '.ucfirst($archivedFoto->status).'.';

        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'counts' => $event->fotoStatusCounts()]);
        }

        return back()->with('success', $message);
    }

    protected function restoreFotoArchive(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        try {
            $restoredCount = $event->restoreArchivedFotoUploads();
        } catch (UniqueConstraintViolationException $exception) {
            report($exception);

            return $this->restoreFailed($request);
        }

        if ($restoredCount === 0) {
            return back()->with('error', 'The archive is empty, there is nothing to restore.');
        }

        ActivityLog::record('foto.archive_restored', ['count' => $restoredCount], $event->id);

        return back()->with('success', "Restored {$restoredCount} ".str('upload')->plural($restoredCount).' to the Foto Queue.');
    }

    private function restoreFailed(Request $request): JsonResponse|RedirectResponse
    {
        $message = 'An upload with the same id is already in the Foto Queue, so nothing was restored. Please contact support.';

        return $request->wantsJson()
            ? response()->json(['message' => $message], 409)
            : back()->with('error', $message);
    }
}
