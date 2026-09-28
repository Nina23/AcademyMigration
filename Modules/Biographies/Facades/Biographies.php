<?php

namespace TypiCMS\Modules\Biographies\Facades;

use Illuminate\Support\Facades\Facade;

class Biographies extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Biographies';
    }
}
