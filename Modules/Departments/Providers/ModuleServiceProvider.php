<?php

namespace TypiCMS\Modules\Departments\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use TypiCMS\Modules\Core\Facades\TypiCMS;
use TypiCMS\Modules\Core\Observers\SlugObserver;
use TypiCMS\Modules\Departments\Composers\SidebarViewComposer;
use TypiCMS\Modules\Departments\Facades\Departments;
use TypiCMS\Modules\Departments\Models\Department;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'typicms.departments');
        $this->mergeConfigFrom(__DIR__.'/../config/permissions.php', 'typicms.permissions');

        $modules = $this->app['config']['typicms']['modules'];
        $this->app['config']->set('typicms.modules', array_merge(['departments' => ['linkable_to_page']], $modules));

        $this->loadViewsFrom(null, 'departments');

        $this->publishes([
            __DIR__.'/../database/migrations/create_departments_table.php.stub' => getMigrationFileName('create_departments_table'),
        ], 'migrations');

        AliasLoader::getInstance()->alias('Departments', Departments::class);

        // Observers
        Department::observe(new SlugObserver());

        /*
         * Sidebar view composer
         */
        $this->app->view->composer('core::admin._sidebar', SidebarViewComposer::class);

        /*
         * Add the page in the view.
         */
        $this->app->view->composer('departments::public.*', function ($view) {
            $view->page = TypiCMS::getPageLinkedToModule('departments');
        });
    }

    public function register()
    {
        $app = $this->app;

        /*
         * Register route service provider
         */
        $app->register(RouteServiceProvider::class);

        $app->bind('Departments', Department::class);
    }
}
