<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DownloadMediaRequest;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\FotoUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaDownloadController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $events = Event::query()
            ->withCount([
                'fotoUploads as uploads_count',
                'fotoUploads as photos_count' => fn (Builder $query) => $query->where('media_type', 'photo'),
                'fotoUploads as videos_count' => fn (Builder $query) => $query->where('media_type', 'video'),
            ])
            ->withSum('fotoUploads as uploads_bytes', 'file_size')
            ->latest('id')
            ->get();

        $summary = [
            'uploads' => FotoUpload::query()->count(),
            'photos' => FotoUpload::query()->where('media_type', 'photo')->count(),
            'videos' => FotoUpload::query()->where('media_type', 'video')->count(),
            'bytes' => (int) FotoUpload::query()->sum('file_size'),
        ];

        $recentUploads = FotoUpload::query()
            ->with('event:id,name,slug')
            ->latest()
            ->limit(6)
            ->get();

        return view('admin.media-downloads.index', [
            'events' => $events,
            'summary' => $summary,
            'recentUploads' => $recentUploads,
        ]);
    }

    public function download(DownloadMediaRequest $request): BinaryFileResponse|RedirectResponse
    {
        $filters = $request->validated();
        $query = $this->filteredUploadsQuery($filters);

        if (! (clone $query)->exists()) {
            return back()
                ->withInput()
                ->with('error', 'No uploaded media matches those filters.');
        }

        $temporaryDirectory = storage_path('app/media-downloads');
        File::ensureDirectoryExists($temporaryDirectory);

        $archiveName = $this->archiveName($filters);
        $archivePath = $temporaryDirectory.DIRECTORY_SEPARATOR.Str::uuid().'-'.$archiveName;
        $manifestPath = $temporaryDirectory.DIRECTORY_SEPARATOR.Str::uuid().'-manifest.csv';

        try {
            [$addFile, $closeArchive] = $this->createArchive($archivePath);
        } catch (\RuntimeException $exception) {
            report($exception);

            return back()->withInput()->with('error', 'ZIP downloads are not available on this server.');
        }

        $manifest = null;
        if ($request->boolean('include_manifest')) {
            $manifest = fopen($manifestPath, 'wb');

            if ($manifest !== false) {
                fputcsv($manifest, [
                    'Archive path',
                    'Event',
                    'Media type',
                    'Original filename',
                    'Uploader name',
                    'Uploader phone',
                    'Status',
                    'Uploaded at',
                    'File size',
                    'MIME type',
                ]);
            }
        }

        $filesAdded = 0;

        $query->with('event:id,name,slug')
            ->lazyById(200)
            ->each(function (FotoUpload $upload) use ($addFile, $manifest, &$filesAdded): void {
                $disk = Storage::disk('public');

                if (! $disk->exists($upload->file_path)) {
                    return;
                }

                $archivePath = $this->uploadArchivePath($upload);
                $addFile($disk->path($upload->file_path), $archivePath);
                $filesAdded++;

                if (is_resource($manifest)) {
                    fputcsv($manifest, [
                        $this->safeCsvValue($archivePath),
                        $this->safeCsvValue($upload->event?->name),
                        $upload->media_type,
                        $this->safeCsvValue($upload->original_filename),
                        $this->safeCsvValue($upload->uploader_name),
                        $this->safeCsvValue($upload->uploader_phone),
                        $upload->status,
                        $upload->created_at?->toIso8601String(),
                        $upload->file_size,
                        $upload->mime_type,
                    ]);
                }
            });

        if (is_resource($manifest)) {
            fclose($manifest);
            $addFile($manifestPath, 'manifest.csv');
        }

        $closeArchive();
        unset($addFile, $closeArchive);
        File::delete($manifestPath);

        if ($filesAdded === 0 || ! File::exists($archivePath)) {
            File::delete($archivePath);

            return back()
                ->withInput()
                ->with('error', 'The matching uploads are no longer available in storage.');
        }

        ActivityLog::record('media.downloaded', [
            'event_id' => $filters['event_id'] ?? null,
            'media_type' => $filters['media_type'],
            'status' => $filters['status'],
            'files' => $filesAdded,
        ], isset($filters['event_id']) ? (int) $filters['event_id'] : null);

        return response()
            ->download($archivePath, $archiveName, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filteredUploadsQuery(array $filters): Builder
    {
        return FotoUpload::query()
            ->when(
                $filters['event_id'] ?? null,
                fn (Builder $query, mixed $eventId) => $query->where('event_id', $eventId)
            )
            ->when(
                $filters['media_type'] !== 'all',
                fn (Builder $query) => $query->where('media_type', $filters['media_type'])
            )
            ->when(
                $filters['status'] !== 'all',
                fn (Builder $query) => $query->where('status', $filters['status'])
            )
            ->orderBy('id');
    }

    /**
     * @return array{0: callable(string, string): void, 1: callable(): void}
     */
    private function createArchive(string $archivePath): array
    {
        if (class_exists(\ZipArchive::class)) {
            $archive = new \ZipArchive;

            if ($archive->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Unable to create the ZIP archive.');
            }

            return [
                static function (string $filePath, string $archiveFilePath) use ($archive): void {
                    $archive->addFile($filePath, $archiveFilePath);
                },
                static function () use ($archive): void {
                    $archive->close();
                },
            ];
        }

        if (class_exists(\PharData::class)) {
            $archive = new \PharData($archivePath, 0, null, \Phar::ZIP);

            return [
                static function (string $filePath, string $archiveFilePath) use ($archive): void {
                    $archive->addFile($filePath, $archiveFilePath);
                },
                static function () use (&$archive): void {
                    unset($archive);
                },
            ];
        }

        throw new \RuntimeException('No ZIP archive extension is installed.');
    }

    private function uploadArchivePath(FotoUpload $upload): string
    {
        $eventFolder = Str::slug($upload->event?->slug ?: $upload->event?->name ?: 'event-'.$upload->event_id);
        $typeFolder = $upload->isVideo() ? 'videos' : 'images';
        $originalName = basename($upload->original_filename ?: $upload->file_path);
        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '-', $originalName) ?: basename($upload->file_path);

        return $eventFolder.'/'.$typeFolder.'/'.str_pad((string) $upload->id, 6, '0', STR_PAD_LEFT).'-'.$safeName;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function archiveName(array $filters): string
    {
        $scope = 'all-events';

        if (! empty($filters['event_id'])) {
            $scope = Event::query()->whereKey($filters['event_id'])->value('slug') ?: 'event-'.$filters['event_id'];
        }

        $media = $filters['media_type'] === 'all' ? 'media' : Str::plural($filters['media_type']);
        $status = $filters['status'] === 'all' ? '' : '-'.$filters['status'];

        return 'eventbomb-'.$scope.'-'.$media.$status.'-'.now()->format('Y-m-d-His').'.zip';
    }

    private function safeCsvValue(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
