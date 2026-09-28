<?php

namespace TypiCMS\Modules\Biographies\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use TypiCMS\Modules\Core\Facades\TypiCMS;
use TypiCMS\Modules\Core\Observers\SlugObserver;
use TypiCMS\Modules\Biographies\Composers\SidebarViewComposer;
use TypiCMS\Modules\Biographies\Facades\Biographies;
use TypiCMS\Modules\Biographies\Models\Biography;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'typicms.biographies');
        $this->mergeConfigFrom(__DIR__.'/../config/permissions.php', 'typicms.permissions');

        $modules = $this->app['config']['typicms']['modules'];
        $this->app['config']->set('typicms.modules', array_merge(['biographies' => ['linkable_to_page']], $modules));

        $this->loadViewsFrom(null, 'biographies');

        $this->publishes([
            __DIR__.'/../database/migrations/create_biographies_table.php.stub' => getMigrationFileName('create_biographies_table'),
        ], 'migrations');

        AliasLoader::getInstance()->alias('Biographies', Biographies::class);

        // Observers
        Biography::observe(new SlugObserver());

        /*
         * Sidebar view composer
         */
        $this->app->view->composer('core::admin._sidebar', SidebarViewComposer::class);

        /*
         * Add the page in the view.
         */
        $this->app->view->composer('biographies::public.*', function ($view) {
            $pages = TypiCMS::getPagesLinkedToModule('biographies');
            $route=\Route::current();
            $urlArray= explode('/',$route->uri);
            $categorySlug=end($urlArray);
            
            foreach($pages as $page){
                if($categorySlug===$page->slug){
                    $view->page = $page;
                }

            }
            if(!$view->page){
            $view->page = TypiCMS::getPageLinkedToModule('biographies');
            }
            
        });
    }

    public function register()
    {
        $app = $this->app;

        /*
         * Register route service provider
         */
        $app->register(RouteServiceProvider::class);

        $app->bind('Biographies', Biography::class);
    }
}
