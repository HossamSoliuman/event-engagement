<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Traits\CalculatesEventStatistics;
use Illuminate\Contracts\View\View;

class StatisticsController extends Controller
{
    use CalculatesEventStatistics;

    public function index(Event $event): View
    {
        [$from, $to] = $this->eventRange($event);

        return view('moderator.statistics', [
            'event' => $event,
            'stats' => $this->buildStatistics($event, $from, $to),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function report(Event $event): View
    {
        [$from, $to] = $this->eventRange($event);

        return view('admin.statistics.report', [
            'event' => $event,
            'stats' => $this->buildStatistics($event, $from, $to),
            'from' => $from,
            'to' => $to,
        ]);
    }
}
