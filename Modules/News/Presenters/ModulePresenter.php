<?php

namespace TypiCMS\Modules\News\Presenters;
use Illuminate\Support\Facades\Storage;

use TypiCMS\Modules\Core\Presenters\Presenter;

class ModulePresenter extends Presenter
{

    protected function getImagePathOrDefault()
    {

        $path = '';

        if (is_object($this->entity->image)) {
            $path = Storage::url($this->entity->image->path);
        }
        return $path;

    }

    public function image($width = null, $height = null, array $options = [])
    {
        $path = $this->getImagePathOrDefault();
        return $path;
    }

    public function dateLocalized($column = 'date')
    {
        return $this->entity->{$column}->format('d.m.Y');
    }

}
