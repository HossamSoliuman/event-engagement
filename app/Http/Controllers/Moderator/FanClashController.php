<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Admin\FanClashController as AdminFanClashController;

class FanClashController extends AdminFanClashController
{
    protected function indexView(): string
    {
        return 'moderator.fanclash.index';
    }

    protected function routePrefix(): string
    {
        return 'moderator.';
    }
}
