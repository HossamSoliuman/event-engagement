<?php

namespace App\Http\Controllers;

class ArtisanController extends Controller
{
    public function __invoke()
    {
        $source = storage_path('app/public');
        $dest   = public_path('storage');

        if (!file_exists($source)) {
            return 'Source folder does not exist.';
        }

        $this->copyFolder($source, $dest);

        return 'Done.';
    }

    private function copyFolder(string $source, string $dest): void
    {
        if (!file_exists($dest)) {
            mkdir($dest, 0755, true);
        }

        foreach (scandir($source) as $item) {
            if ($item === '.' || $item === '..') continue;

            $from = $source . DIRECTORY_SEPARATOR . $item;
            $to   = $dest   . DIRECTORY_SEPARATOR . $item;

            is_dir($from) ? $this->copyFolder($from, $to) : copy($from, $to);
        }
    }
}