<?php

namespace TypiCMS\Modules\Newsletters\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use TypiCMS\Modules\Core\Facades\TypiCMS;
use TypiCMS\Modules\Core\Observers\SlugObserver;
use TypiCMS\Modules\Newsletters\Composers\SidebarViewComposer;
use TypiCMS\Modules\Newsletters\Facades\Newsletters;
use TypiCMS\Modules\Newsletters\Models\Newsletter;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'typicms.newsletters');
        $this->mergeConfigFrom(__DIR__.'/../config/permissions.php', 'typicms.permissions');

        $modules = $this->app['config']['typicms']['modules'];
        $this->app['config']->set('typicms.modules', array_merge(['newsletters' => ['linkable_to_page']], $modules));

        $this->loadViewsFrom(null, 'newsletters');

        $this->publishes([
            __DIR__.'/../database/migrations/create_newsletters_table.php.stub' => getMigrationFileName('create_newsletters_table'),
        ], 'migrations');

        AliasLoader::getInstance()->alias('Newsletters', Newsletters::class);

        // Observers
        Newsletter::observe(new SlugObserver());

        /*
         * Sidebar view composer
         */
        $this->app->view->composer('core::admin._sidebar', SidebarViewComposer::class);

        /*
         * Add the page in the view.
         */
        $this->app->view->composer('newsletters::public.*', function ($view) {
            $view->page = TypiCMS::getPageLinkedToModule('newsletters');
        });
    }

    public function register()
    {
        $app = $this->app;

        /*
         * Register route service provider
         */
        $app->register(RouteServiceProvider::class);

        $app->bind('Newsletters', Newsletter::class);
    }
}
