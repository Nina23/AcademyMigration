<?php

namespace TypiCMS\Modules\Files\Presenters;

use Exception;
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

        // No Storage::exists() here: on S3 it is one HTTP request per file, and the
        // file manager serializes every file of a folder (and its children).
        $path = '';
        if ($this->entity->path) {
            try {
                $path = Storage::url($this->entity->path);
            } catch (Exception $e) {
            }
        }
        return $path;

    }

    public function image($width = null, $height = null, array $options = [])
    {
        $path = $this->getImagePathOrDefault();
        return $path;
    }
}
