<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Admin\LotteryAdminController;
use App\Models\Event;
use App\Models\LotteryEntry;
use Illuminate\Http\RedirectResponse;

class LotteryController extends LotteryAdminController
{
    public function destroyEntry(Event $event, LotteryEntry $lotteryEntry): RedirectResponse
    {
        return $this->removeEntry($lotteryEntry);
    }

    protected function indexView(): string
    {
        return 'moderator.lottery.index';
    }

    protected function routePrefix(): string
    {
        return 'moderator.';
    }
}
