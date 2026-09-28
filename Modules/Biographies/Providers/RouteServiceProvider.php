<?php

namespace TypiCMS\Modules\Biographies\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use TypiCMS\Modules\Core\Facades\TypiCMS;
use TypiCMS\Modules\Biographies\Http\Controllers\AdminController;
use TypiCMS\Modules\Biographies\Http\Controllers\ApiController;
use TypiCMS\Modules\Biographies\Http\Controllers\PublicController;

class RouteServiceProvider extends ServiceProvider
{
    public function map()
    {
        /*
         * Front office routes
         */
        $pages = TypiCMS::getPagesLinkedToModule('biographies');
        foreach($pages as $page){
            $middleware = $page->private ? ['public', 'auth'] : ['public'];
            foreach (locales() as $lang) {
                if ($page->isPublished($lang) && $uri = $page->uri($lang)) {
                    Route::middleware($middleware)->prefix($uri)->name($lang.'::')->group(function (Router $router) {
                        $router->get('/', [PublicController::class, 'index'])->name('index-biographies');
                        $router->get('{slug}', [PublicController::class, 'show'])->name('biography');
                    });
                }
            }
        }
        

        /*
         * Admin routes
         */
        Route::middleware('admin')->prefix('admin')->name('admin::')->group(function (Router $router) {
            $router->get('biographies', [AdminController::class, 'index'])->name('index-biographies')->middleware('can:read biographies');
            $router->get('biographies/export', [AdminController::class, 'export'])->name('admin::export-biographies')->middleware('can:read biographies');
            $router->get('biographies/create', [AdminController::class, 'create'])->name('create-biography')->middleware('can:create biographies');
            $router->get('biographies/{biography}/edit', [AdminController::class, 'edit'])->name('edit-biography')->middleware('can:read biographies');
            $router->post('biographies', [AdminController::class, 'store'])->name('store-biography')->middleware('can:create biographies');
            $router->put('biographies/{biography}', [AdminController::class, 'update'])->name('update-biography')->middleware('can:update biographies');
        });

        /*
         * API routes
         */
        Route::middleware(['api', 'auth:api'])->prefix('api')->group(function (Router $router) {
            $router->get('biographies', [ApiController::class, 'index'])->middleware('can:read biographies');
            $router->patch('biographies/{biography}', [ApiController::class, 'updatePartial'])->middleware('can:update biographies');
            $router->delete('biographies/{biography}', [ApiController::class, 'destroy'])->middleware('can:delete biographies');
        });
    }
}
