<?php

namespace TypiCMS\Modules\Newsletters\Facades;

use Illuminate\Support\Facades\Facade;

class Newsletters extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Newsletters';
    }
}
