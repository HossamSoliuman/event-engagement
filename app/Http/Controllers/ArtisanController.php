<?php

namespace App\Http\Controllers;

class ArtisanController extends Controller
{
    public function __invoke()
    {
        $source = storage_path('app/public');
        $dest   = public_path('storage');

        return [
            'source'        => $source,
            'dest'          => $dest,
            'source_exists' => file_exists($source),
            'dest_exists'   => file_exists($dest),
            'source_files'  => file_exists($source) ? scandir($source) : [],
            'dest_files'    => file_exists($dest)   ? scandir($dest)   : [],
        ];
    }
}