<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;

class ArtisanController extends Controller
{
    public function __invoke()
    {
        Artisan::call('storage:link');

        return Artisan::output();
    }
}
