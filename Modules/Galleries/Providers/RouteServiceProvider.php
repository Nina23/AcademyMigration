<?php

namespace TypiCMS\Modules\Galleries\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use TypiCMS\Modules\Core\Facades\TypiCMS;
use TypiCMS\Modules\Galleries\Http\Controllers\AdminController;
use TypiCMS\Modules\Galleries\Http\Controllers\ApiController;
use TypiCMS\Modules\Galleries\Http\Controllers\PublicController;

class RouteServiceProvider extends ServiceProvider
{
    public function map()
    {
        /*
         * Front office routes
         */
        if ($page = TypiCMS::getPageLinkedToModule('galleries')) {
            $middleware = $page->private ? ['public', 'auth'] : ['public'];
            foreach (locales() as $lang) {
                if ($page->isPublished($lang) && $uri = $page->uri($lang)) {
                    Route::middleware($middleware)->prefix($uri)->name($lang.'::')->group(function (Router $router) {
                        $router->get('/', [PublicController::class, 'index'])->name('index-galleries');
                        $router->get('{slug}', [PublicController::class, 'show'])->name('gallery');
                    });
                }
            }
        }

        /*
         * Admin routes
         */
        Route::middleware('admin')->prefix('admin')->name('admin::')->group(function (Router $router) {
            $router->get('galleries', [AdminController::class, 'index'])->name('index-galleries')->middleware('can:read galleries');
            $router->get('galleries/export', [AdminController::class, 'export'])->name('admin::export-galleries')->middleware('can:read galleries');
            $router->get('galleries/create', [AdminController::class, 'create'])->name('create-gallery')->middleware('can:create galleries');
            $router->get('galleries/{gallery}/edit', [AdminController::class, 'edit'])->name('edit-gallery')->middleware('can:read galleries');
            $router->post('galleries', [AdminController::class, 'store'])->name('store-gallery')->middleware('can:create galleries');
            $router->put('galleries/{gallery}', [AdminController::class, 'update'])->name('update-gallery')->middleware('can:update galleries');
        });

        /*
         * API routes
         */
        Route::middleware(['api', 'auth:api'])->prefix('api')->group(function (Router $router) {
            $router->get('galleries', [ApiController::class, 'index'])->middleware('can:read galleries');
            $router->patch('galleries/{gallery}', [ApiController::class, 'updatePartial'])->middleware('can:update galleries');
            $router->delete('galleries/{gallery}', [ApiController::class, 'destroy'])->middleware('can:delete galleries');
        });
    }
}
