<?php

namespace TypiCMS\Modules\Banners\Presenters;

use TypiCMS\Modules\Core\Presenters\Presenter;
use Illuminate\Support\Facades\Storage;

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
}
