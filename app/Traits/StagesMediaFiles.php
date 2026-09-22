<?php

namespace App\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Copies files off the media disk into a local staging directory so tools that
 * need a real filesystem path (ZipArchive, PharData) work whether media lives
 * on local storage or S3.
 */
trait StagesMediaFiles
{
    /** @var list<string> */
    private array $stagedMediaFiles = [];

    protected function mediaStagingDirectory(): string
    {
        $directory = storage_path('app/media-downloads');
        File::ensureDirectoryExists($directory);

        return $directory;
    }

    /**
     * Stream a media-disk file to a local temporary copy and return its path,
     * or null when the file no longer exists.
     */
    protected function stageMediaFile(string $path): ?string
    {
        $disk = Storage::disk('media');

        if (! $disk->exists($path)) {
            return null;
        }

        $source = $disk->readStream($path);

        if (! is_resource($source)) {
            return null;
        }

        $localPath = $this->mediaStagingDirectory().DIRECTORY_SEPARATOR.Str::uuid().'-'.basename($path);
        $target = fopen($localPath, 'wb');

        if ($target === false) {
            fclose($source);

            return null;
        }

        stream_copy_to_stream($source, $target);
        fclose($target);
        fclose($source);

        $this->stagedMediaFiles[] = $localPath;

        return $localPath;
    }

    /**
     * Remove every staged copy. Call this only after the archive is closed,
     * since ZipArchive reads its sources on close().
     */
    protected function discardStagedMediaFiles(): void
    {
        File::delete($this->stagedMediaFiles);
        $this->stagedMediaFiles = [];
    }
}
