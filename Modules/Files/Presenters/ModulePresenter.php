<?php

namespace TypiCMS\Modules\Files\Presenters;

use Illuminate\Support\Facades\Storage;
use TypiCMS\Modules\Core\Presenters\Presenter;
use Illuminate\Support\Facades\Log;

class ModulePresenter extends Presenter
{

    /**
     * Get title.
     *
     * @return string
     */
    public function title()
    {
        return $this->entity->name;
    }

    /**
     * Format file size.
     *
     * @param mixed $precision
     *
     * @return string
     */
    public function filesize($precision = 0)
    {
        $base = log($this->entity->filesize, 1024);
        $suffixes = ['', __('KB'), __('MB'), __('GB'), __('TB')];

        return round(pow(1024, $base - floor($base)), $precision).' '.$suffixes[floor($base)];
    }

    protected function getImagePathOrDefault()
    {

        $path = '';
        if (Storage::exists($this->entity->path)) {
            $path = Storage::url($this->entity->path);
        }
        return $path;

    }

    public function image($width = null, $height = null, array $options = [])
    {
        $path = $this->getImagePathOrDefault();
        return $path;
    }
}
