<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;

class ArtisanController extends Controller
{
    public function __invoke()
    {
        $target = storage_path('app/public');
        $link   = public_path('storage');

        if (file_exists($link)) {
            return 'Already exists.';
        }

        exec("ln -s {$target} {$link}", $output, $code);

        return $code === 0 ? 'Storage linked successfully.' : 'Failed: ' . implode("\n", $output);
    }
}