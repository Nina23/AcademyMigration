<?php

namespace TypiCMS\Modules\Categories\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use TypiCMS\Modules\Core\Facades\TypiCMS;
use TypiCMS\Modules\Core\Observers\SlugObserver;
use TypiCMS\Modules\Categories\Composers\SidebarViewComposer;
use TypiCMS\Modules\Categories\Facades\Categories;
use TypiCMS\Modules\Categories\Models\Category;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'typicms.categories');
        $this->mergeConfigFrom(__DIR__.'/../config/permissions.php', 'typicms.permissions');

        $modules = $this->app['config']['typicms']['modules'];
        $this->app['config']->set('typicms.modules', array_merge(['categories' => ['linkable_to_page']], $modules));

        $this->loadViewsFrom(null, 'categories');

        $this->publishes([
            __DIR__.'/../database/migrations/create_categories_table.php.stub' => getMigrationFileName('create_categories_table'),
        ], 'migrations');

        AliasLoader::getInstance()->alias('Categories', Categories::class);

        // Observers
        Category::observe(new SlugObserver());

        /*
         * Sidebar view composer
         */
        $this->app->view->composer('core::admin._sidebar', SidebarViewComposer::class);

        /*
         * Add the page in the view.
         */
        $this->app->view->composer('categories::public.*', function ($view) {
            $view->page = TypiCMS::getPageLinkedToModule('categories');
        });
    }

    public function register()
    {
        $app = $this->app;

        /*
         * Register route service provider
         */
        $app->register(RouteServiceProvider::class);

        $app->bind('Categories', Category::class);
    }
}
