<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Exposes the media-disk paths a model row points at, so event deletion and
 * the orphan-pruning command share one definition of "referenced file".
 * Models list their path columns in a MEDIA_COLUMNS constant.
 */
trait ReferencesMediaFiles
{
    /**
     * Non-empty media-disk paths stored on this row.
     *
     * @return Collection<int, string>
     */
    public function mediaPaths(): Collection
    {
        return collect(static::MEDIA_COLUMNS)
            ->map(fn (string $column) => $this->getAttribute($column))
            ->filter()
            ->values();
    }

    /**
     * Distinct media-disk paths stored on every row the query matches.
     *
     * @return Collection<int, string>
     */
    public static function allMediaPaths(?Builder $query = null): Collection
    {
        return ($query ?? static::query())
            ->get()
            ->flatMap(fn (self $row) => $row->mediaPaths())
            ->unique()
            ->values();
    }
}
