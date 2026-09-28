<?php

namespace TypiCMS\Modules\Announcements\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use TypiCMS\Modules\Core\Facades\TypiCMS;
use TypiCMS\Modules\Core\Observers\SlugObserver;
use TypiCMS\Modules\Announcements\Composers\SidebarViewComposer;
use TypiCMS\Modules\Announcements\Facades\Announcements;
use TypiCMS\Modules\Announcements\Models\Announcement;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'typicms.announcements');
        $this->mergeConfigFrom(__DIR__.'/../config/permissions.php', 'typicms.permissions');

        $modules = $this->app['config']['typicms']['modules'];
        $this->app['config']->set('typicms.modules', array_merge(['announcements' => ['linkable_to_page']], $modules));

        $this->loadViewsFrom(null, 'announcements');

        $this->publishes([
            __DIR__.'/../database/migrations/create_announcements_table.php.stub' => getMigrationFileName('create_announcements_table'),
        ], 'migrations');

        AliasLoader::getInstance()->alias('Announcements', Announcements::class);

        // Observers
        Announcement::observe(new SlugObserver());

        /*
         * Sidebar view composer
         */
        $this->app->view->composer('core::admin._sidebar', SidebarViewComposer::class);

        /*
         * Add the page in the view.
         */
        $this->app->view->composer('announcements::public.*', function ($view) {
            $view->page = TypiCMS::getPageLinkedToModule('announcements');
        });
    }

    public function register()
    {
        $app = $this->app;

        /*
         * Register route service provider
         */
        $app->register(RouteServiceProvider::class);

        $app->bind('Announcements', Announcement::class);
    }
}
