<?php

namespace TypiCMS\Modules\Banners\Facades;

use Illuminate\Support\Facades\Facade;

class Banners extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Banners';
    }
}
