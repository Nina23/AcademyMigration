<?php

namespace TypiCMS\Modules\Categories\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use TypiCMS\Modules\Core\Facades\TypiCMS;
use TypiCMS\Modules\Categories\Http\Controllers\AdminController;
use TypiCMS\Modules\Categories\Http\Controllers\ApiController;
use TypiCMS\Modules\Categories\Http\Controllers\PublicController;

class RouteServiceProvider extends ServiceProvider
{
    public function map()
    {
        /*
         * Front office routes
         */
        if ($page = TypiCMS::getPageLinkedToModule('categories')) {
            $middleware = $page->private ? ['public', 'auth'] : ['public'];
            foreach (locales() as $lang) {
                if ($page->isPublished($lang) && $uri = $page->uri($lang)) {
                    Route::middleware($middleware)->prefix($uri)->name($lang.'::')->group(function (Router $router) {
                        $router->get('/', [PublicController::class, 'index'])->name('index-categories');
                        $router->get('{slug}', [PublicController::class, 'show'])->name('category');
                    });
                }
            }
        }

        /*
         * Admin routes
         */
        Route::middleware('admin')->prefix('admin')->name('admin::')->group(function (Router $router) {
            $router->get('categories', [AdminController::class, 'index'])->name('index-categories')->middleware('can:read categories');
            $router->get('categories/export', [AdminController::class, 'export'])->name('admin::export-categories')->middleware('can:read categories');
            $router->get('categories/create', [AdminController::class, 'create'])->name('create-category')->middleware('can:create categories');
            $router->get('categories/{category}/edit', [AdminController::class, 'edit'])->name('edit-category')->middleware('can:read categories');
            $router->post('categories', [AdminController::class, 'store'])->name('store-category')->middleware('can:create categories');
            $router->put('categories/{category}', [AdminController::class, 'update'])->name('update-category')->middleware('can:update categories');
        });

        /*
         * API routes
         */
        Route::middleware(['api', 'auth:api'])->prefix('api')->group(function (Router $router) {
            $router->get('categories', [ApiController::class, 'index'])->middleware('can:read categories');
            $router->patch('categories/{category}', [ApiController::class, 'updatePartial'])->middleware('can:update categories');
            $router->delete('categories/{category}', [ApiController::class, 'destroy'])->middleware('can:delete categories');
        });
    }
}
