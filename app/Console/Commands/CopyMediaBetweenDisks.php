<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class CopyMediaBetweenDisks extends Command
{
    protected $signature = 'media:copy
        {--from=public : Disk holding the current media}
        {--to=s3 : Disk to copy the media onto}
        {--overwrite : Re-upload files that already exist on the destination}
        {--dry-run : List what would be copied without writing anything}';

    protected $description = 'Copy every media file (uploads, logos, QR codes) from one filesystem disk to another';

    public function handle(): int
    {
        $sourceName = (string) $this->option('from');
        $destinationName = (string) $this->option('to');

        if ($sourceName === $destinationName) {
            $this->error('Source and destination disks must differ.');

            return self::FAILURE;
        }

        $source = Storage::disk($sourceName);
        $destination = Storage::disk($destinationName);
        $files = array_values(array_filter(
            $source->allFiles(),
            fn (string $path) => ! str_starts_with(basename($path), '.')
        ));

        $this->info(sprintf('Found %d file(s) on the "%s" disk.', count($files), $sourceName));

        $copied = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($files as $path) {
            if (! $this->option('overwrite') && $destination->exists($path)) {
                $skipped++;
                $this->line("  skip  {$path}");

                continue;
            }

            if ($this->option('dry-run')) {
                $copied++;
                $this->line("  copy  {$path}");

                continue;
            }

            if ($this->copyFile($source, $destination, $path)) {
                $copied++;
                $this->line("  ok    {$path}");
            } else {
                $failed++;
                $this->error("  FAIL  {$path}");
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%s %d, skipped %d, failed %d.',
            $this->option('dry-run') ? 'Would copy' : 'Copied',
            $copied,
            $skipped,
            $failed
        ));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function copyFile(Filesystem $source, Filesystem $destination, string $path): bool
    {
        $stream = $source->readStream($path);

        if (! is_resource($stream)) {
            return false;
        }

        try {
            return $destination->writeStream($path, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
