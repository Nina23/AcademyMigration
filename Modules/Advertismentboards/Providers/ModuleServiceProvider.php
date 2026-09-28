<?php

namespace TypiCMS\Modules\Advertismentboards\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use TypiCMS\Modules\Core\Facades\TypiCMS;
use TypiCMS\Modules\Core\Observers\SlugObserver;
use TypiCMS\Modules\Advertismentboards\Composers\SidebarViewComposer;
use TypiCMS\Modules\Advertismentboards\Facades\Advertismentboards;
use TypiCMS\Modules\Advertismentboards\Models\Advertismentboard;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'typicms.advertismentboards');
        $this->mergeConfigFrom(__DIR__.'/../config/permissions.php', 'typicms.permissions');

        $modules = $this->app['config']['typicms']['modules'];
        $this->app['config']->set('typicms.modules', array_merge(['advertismentboards' => ['linkable_to_page']], $modules));

        $this->loadViewsFrom(null, 'advertismentboards');

        $this->publishes([
            __DIR__.'/../database/migrations/create_advertismentboards_table.php.stub' => getMigrationFileName('create_advertismentboards_table'),
        ], 'migrations');

        AliasLoader::getInstance()->alias('Advertismentboards', Advertismentboards::class);

        // Observers
        Advertismentboard::observe(new SlugObserver());

        /*
         * Sidebar view composer
         */
        $this->app->view->composer('core::admin._sidebar', SidebarViewComposer::class);

        /*
         * Add the page in the view.
         */
        $this->app->view->composer('advertismentboards::public.*', function ($view) {
            $view->page = TypiCMS::getPageLinkedToModule('advertismentboards');
        });
    }

    public function register()
    {
        $app = $this->app;

        /*
         * Register route service provider
         */
        $app->register(RouteServiceProvider::class);

        $app->bind('Advertismentboards', Advertismentboard::class);
    }
}
