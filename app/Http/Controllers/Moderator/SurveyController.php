<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Admin\SurveyController as AdminSurveyController;

class SurveyController extends AdminSurveyController
{
    protected function indexView(): string
    {
        return 'moderator.survey.index';
    }

    protected function routePrefix(): string
    {
        return 'moderator.';
    }
}
