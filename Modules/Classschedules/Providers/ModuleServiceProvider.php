<?php

namespace TypiCMS\Modules\Classschedules\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use TypiCMS\Modules\Core\Facades\TypiCMS;
use TypiCMS\Modules\Core\Observers\SlugObserver;
use TypiCMS\Modules\Classschedules\Composers\SidebarViewComposer;
use TypiCMS\Modules\Classschedules\Facades\Classschedules;
use TypiCMS\Modules\Classschedules\Models\Classschedule;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'typicms.classschedules');
        $this->mergeConfigFrom(__DIR__.'/../config/permissions.php', 'typicms.permissions');

        $modules = $this->app['config']['typicms']['modules'];
        $this->app['config']->set('typicms.modules', array_merge(['classschedules' => ['linkable_to_page']], $modules));

        $this->loadViewsFrom(null, 'classschedules');

        $this->publishes([
            __DIR__.'/../database/migrations/create_classschedules_table.php.stub' => getMigrationFileName('create_classschedules_table'),
        ], 'migrations');

        AliasLoader::getInstance()->alias('Classschedules', Classschedules::class);

        // Observers
        Classschedule::observe(new SlugObserver());

        /*
         * Sidebar view composer
         */
        $this->app->view->composer('core::admin._sidebar', SidebarViewComposer::class);

        /*
         * Add the page in the view.
         */
        $this->app->view->composer('classschedules::public.*', function ($view) {
            $view->page = TypiCMS::getPageLinkedToModule('classschedules');
        });
    }

    public function register()
    {
        $app = $this->app;

        /*
         * Register route service provider
         */
        $app->register(RouteServiceProvider::class);

        $app->bind('Classschedules', Classschedule::class);
    }
}
