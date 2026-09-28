<?php

namespace TypiCMS\Modules\Announcements\Facades;

use Illuminate\Support\Facades\Facade;

class Announcements extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Announcements';
    }
}
