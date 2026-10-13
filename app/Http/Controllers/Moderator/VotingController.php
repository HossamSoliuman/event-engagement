<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Admin\VotingAdminController;

class VotingController extends VotingAdminController
{
    protected function indexView(): string
    {
        return 'moderator.voting.index';
    }

    protected function routePrefix(): string
    {
        return 'moderator.';
    }
}
