<?php

namespace TypiCMS\Modules\Announcements\Presenters;

use TypiCMS\Modules\Core\Presenters\Presenter;
Use Illuminate\Support\Carbon;

class ModulePresenter extends Presenter
{
    public function dateLocalized($column = 'date')
    {
        return $this->entity->{$column}->format('d.m.Y');
    }
}
