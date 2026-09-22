<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\FanClashMatchup;
use App\Models\FanClashRound;
use App\Models\FotoUpload;
use App\Models\QuizQuestion;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class PruneOrphanedMedia extends Command
{
    protected $signature = 'media:prune
        {--dry-run : List orphaned files without deleting anything}
        {--min-age=60 : Leave files modified within this many minutes alone, so in-flight uploads survive (0 disables)}';

    protected $description = 'Delete files on the media disk that no event, upload, question, matchup, user or setting references anymore';

    public function handle(): int
    {
        $disk = Storage::disk('media');
        $minAgeMinutes = max(0, (int) $this->option('min-age'));
        $cutoff = now()->subMinutes($minAgeMinutes)->getTimestamp();

        $referenced = $this->referencedPaths()->flip();
        $files = collect($disk->allFiles())
            ->reject(fn (string $path) => str_starts_with(basename($path), '.'))
            ->values();

        $this->info(sprintf(
            'Found %d file(s) on the media disk, %d referenced in the database.',
            $files->count(),
            $referenced->count()
        ));

        $deleted = 0;
        $recent = 0;
        $failed = 0;

        foreach ($files as $path) {
            if ($referenced->has($path)) {
                continue;
            }

            if ($minAgeMinutes > 0 && $this->modifiedAfter($disk, $path, $cutoff)) {
                $recent++;
                $this->line("  recent  {$path}");

                continue;
            }

            if ($this->option('dry-run')) {
                $deleted++;
                $this->line("  orphan  {$path}");

                continue;
            }

            if ($disk->delete($path)) {
                $deleted++;
                $this->line("  deleted {$path}");
            } else {
                $failed++;
                $this->error("  FAIL    {$path}");
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%s %d orphan(s), skipped %d recent file(s), failed %d.',
            $this->option('dry-run') ? 'Would delete' : 'Deleted',
            $deleted,
            $recent,
            $failed
        ));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Every media-disk path still pointed at by a database row. Soft-deleted
     * events are included since they can be restored.
     *
     * @return Collection<int, string>
     */
    private function referencedPaths(): Collection
    {
        return Event::allMediaPaths(Event::withTrashed())
            ->merge(FotoUpload::allMediaPaths())
            ->merge(QuizQuestion::allMediaPaths())
            ->merge(FanClashMatchup::allMediaPaths())
            ->merge(FanClashRound::allMediaPaths())
            ->merge(User::allMediaPaths())
            ->push(SiteSetting::where('key', 'privacy_policy_path')->value('value'))
            ->filter()
            ->map(fn (string $path) => ltrim(str_replace('\\', '/', $path), '/'))
            ->unique()
            ->values();
    }

    private function modifiedAfter(Filesystem $disk, string $path, int $cutoff): bool
    {
        try {
            return $disk->lastModified($path) > $cutoff;
        } catch (\Throwable) {
            return false;
        }
    }
}
