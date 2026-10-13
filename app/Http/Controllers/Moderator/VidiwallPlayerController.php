<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Admin\VidiwallPlayerController as AdminVidiwallPlayerController;

class VidiwallPlayerController extends AdminVidiwallPlayerController
{
    protected function indexView(): string
    {
        return 'moderator.vidiwall-player.index';
    }

    protected function routePrefix(): string
    {
        return 'moderator.';
    }
}
