<?php

namespace TypiCMS\Modules\Advertismentboards\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use TypiCMS\Modules\Core\Facades\TypiCMS;
use TypiCMS\Modules\Advertismentboards\Http\Controllers\AdminController;
use TypiCMS\Modules\Advertismentboards\Http\Controllers\ApiController;
use TypiCMS\Modules\Advertismentboards\Http\Controllers\PublicController;

class RouteServiceProvider extends ServiceProvider
{
    public function map()
    {
        /*
         * Front office routes
         */
        if ($page = TypiCMS::getPageLinkedToModule('advertismentboards')) {
            $middleware = $page->private ? ['public', 'auth'] : ['public'];
            foreach (locales() as $lang) {
                if ($page->isPublished($lang) && $uri = $page->uri($lang)) {
                    Route::middleware($middleware)->prefix($uri)->name($lang.'::')->group(function (Router $router) {
                        $router->get('/', [PublicController::class, 'index'])->name('index-advertismentboards');
                        $router->get('{slug}', [PublicController::class, 'show'])->name('advertismentboard');
                    });
                }
            }
        }

        /*
         * Admin routes
         */
        Route::middleware('admin')->prefix('admin')->name('admin::')->group(function (Router $router) {
            $router->get('advertismentboards', [AdminController::class, 'index'])->name('index-advertismentboards')->middleware('can:read advertismentboards');
            $router->get('advertismentboards/export', [AdminController::class, 'export'])->name('admin::export-advertismentboards')->middleware('can:read advertismentboards');
            $router->get('advertismentboards/create', [AdminController::class, 'create'])->name('create-advertismentboard')->middleware('can:create advertismentboards');
            $router->get('advertismentboards/{advertismentboard}/edit', [AdminController::class, 'edit'])->name('edit-advertismentboard')->middleware('can:read advertismentboards');
            $router->post('advertismentboards', [AdminController::class, 'store'])->name('store-advertismentboard')->middleware('can:create advertismentboards');
            $router->put('advertismentboards/{advertismentboard}', [AdminController::class, 'update'])->name('update-advertismentboard')->middleware('can:update advertismentboards');
        });

        /*
         * API routes
         */
        Route::middleware(['api', 'auth:api'])->prefix('api')->group(function (Router $router) {
            $router->get('advertismentboards', [ApiController::class, 'index'])->middleware('can:read advertismentboards');
            $router->patch('advertismentboards/{advertismentboard}', [ApiController::class, 'updatePartial'])->middleware('can:update advertismentboards');
            $router->delete('advertismentboards/{advertismentboard}', [ApiController::class, 'destroy'])->middleware('can:delete advertismentboards');
        });
    }
}
