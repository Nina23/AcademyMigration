<?php

namespace TypiCMS\Modules\Banners\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use TypiCMS\Modules\Core\Facades\TypiCMS;
use TypiCMS\Modules\Core\Observers\SlugObserver;
use TypiCMS\Modules\Banners\Composers\SidebarViewComposer;
use TypiCMS\Modules\Banners\Facades\Banners;
use TypiCMS\Modules\Banners\Models\Banner;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'typicms.banners');
        $this->mergeConfigFrom(__DIR__.'/../config/permissions.php', 'typicms.permissions');

        $modules = $this->app['config']['typicms']['modules'];
        $this->app['config']->set('typicms.modules', array_merge(['banners' => ['linkable_to_page']], $modules));

        $this->loadViewsFrom(null, 'banners');

        $this->publishes([
            __DIR__.'/../database/migrations/create_banners_table.php.stub' => getMigrationFileName('create_banners_table'),
        ], 'migrations');

        AliasLoader::getInstance()->alias('Banners', Banners::class);

        // Observers
        Banner::observe(new SlugObserver());

        /*
         * Sidebar view composer
         */
        $this->app->view->composer('core::admin._sidebar', SidebarViewComposer::class);

        /*
         * Add the page in the view.
         */
        $this->app->view->composer('banners::public.*', function ($view) {
            $view->page = TypiCMS::getPageLinkedToModule('banners');
        });
    }

    public function register()
    {
        $app = $this->app;

        /*
         * Register route service provider
         */
        $app->register(RouteServiceProvider::class);

        $app->bind('Banners', Banner::class);
    }
}
