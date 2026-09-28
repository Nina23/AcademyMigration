<?php

namespace TypiCMS\Modules\Departments\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use TypiCMS\Modules\Core\Facades\TypiCMS;
use TypiCMS\Modules\Departments\Http\Controllers\AdminController;
use TypiCMS\Modules\Departments\Http\Controllers\ApiController;
use TypiCMS\Modules\Departments\Http\Controllers\PublicController;

class RouteServiceProvider extends ServiceProvider
{
    public function map()
    {
        /*
         * Front office routes
         */
        if ($page = TypiCMS::getPageLinkedToModule('departments')) {
            $middleware = $page->private ? ['public', 'auth'] : ['public'];
            foreach (locales() as $lang) {
                if ($page->isPublished($lang) && $uri = $page->uri($lang)) {
                    Route::middleware($middleware)->prefix($uri)->name($lang.'::')->group(function (Router $router) {
                        $router->get('/', [PublicController::class, 'index'])->name('index-departments');
                        $router->get('{slug}', [PublicController::class, 'show'])->name('department');
                    });
                }
            }
        }

        /*
         * Admin routes
         */
        Route::middleware('admin')->prefix('admin')->name('admin::')->group(function (Router $router) {
            $router->get('departments', [AdminController::class, 'index'])->name('index-departments')->middleware('can:read departments');
            $router->get('departments/export', [AdminController::class, 'export'])->name('admin::export-departments')->middleware('can:read departments');
            $router->get('departments/create', [AdminController::class, 'create'])->name('create-department')->middleware('can:create departments');
            $router->get('departments/{department}/edit', [AdminController::class, 'edit'])->name('edit-department')->middleware('can:read departments');
            $router->post('departments', [AdminController::class, 'store'])->name('store-department')->middleware('can:create departments');
            $router->put('departments/{department}', [AdminController::class, 'update'])->name('update-department')->middleware('can:update departments');
        });

        /*
         * API routes
         */
        Route::middleware(['api', 'auth:api'])->prefix('api')->group(function (Router $router) {
            $router->get('departments', [ApiController::class, 'index'])->middleware('can:read departments');
            $router->patch('departments/{department}', [ApiController::class, 'updatePartial'])->middleware('can:update departments');
            $router->delete('departments/{department}', [ApiController::class, 'destroy'])->middleware('can:delete departments');
        });
    }
}
