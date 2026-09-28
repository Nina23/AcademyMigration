<?php

namespace TypiCMS\Modules\Newsletters\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use TypiCMS\Modules\Core\Facades\TypiCMS;
use TypiCMS\Modules\Newsletters\Http\Controllers\AdminController;
use TypiCMS\Modules\Newsletters\Http\Controllers\ApiController;
use TypiCMS\Modules\Newsletters\Http\Controllers\PublicController;

class RouteServiceProvider extends ServiceProvider
{
    public function map()
    {
        /*
         * Front office routes
         */
        if ($page = TypiCMS::getPageLinkedToModule('newsletters')) {
            $middleware = $page->private ? ['public', 'auth'] : ['public'];
            foreach (locales() as $lang) {
                if ($page->isPublished($lang) && $uri = $page->uri($lang)) {
                    Route::middleware($middleware)->prefix($uri)->name($lang.'::')->group(function (Router $router) {
                        $router->get('/', [PublicController::class, 'index'])->name('index-newsletters');
                        $router->get('{slug}', [PublicController::class, 'show'])->name('newsletter');
                    });
                }
            }
        }

        /*
         * Admin routes
         */
        Route::middleware('admin')->prefix('admin')->name('admin::')->group(function (Router $router) {
            $router->get('newsletters', [AdminController::class, 'index'])->name('index-newsletters')->middleware('can:read newsletters');
            $router->get('newsletters/export', [AdminController::class, 'export'])->name('admin::export-newsletters')->middleware('can:read newsletters');
            $router->get('newsletters/create', [AdminController::class, 'create'])->name('create-newsletter')->middleware('can:create newsletters');
            $router->get('newsletters/{newsletter}/edit', [AdminController::class, 'edit'])->name('edit-newsletter')->middleware('can:read newsletters');
            $router->post('newsletters', [AdminController::class, 'store'])->name('store-newsletter')->middleware('can:create newsletters');
            $router->put('newsletters/{newsletter}', [AdminController::class, 'update'])->name('update-newsletter')->middleware('can:update newsletters');
        });

        /*
         * API routes
         */
        Route::middleware(['api', 'auth:api'])->prefix('api')->group(function (Router $router) {
            $router->get('newsletters', [ApiController::class, 'index'])->middleware('can:read newsletters');
            $router->patch('newsletters/{newsletter}', [ApiController::class, 'updatePartial'])->middleware('can:update newsletters');
            $router->delete('newsletters/{newsletter}', [ApiController::class, 'destroy'])->middleware('can:delete newsletters');
        });
    }
}
