<?php

namespace TypiCMS\Modules\Announcements\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use TypiCMS\Modules\Core\Facades\TypiCMS;
use TypiCMS\Modules\Announcements\Http\Controllers\AdminController;
use TypiCMS\Modules\Announcements\Http\Controllers\ApiController;
use TypiCMS\Modules\Announcements\Http\Controllers\PublicController;

class RouteServiceProvider extends ServiceProvider
{
    public function map()
    {
        /*
         * Front office routes
         */
        if ($page = TypiCMS::getPageLinkedToModule('announcements')) {
            $middleware = $page->private ? ['public', 'auth'] : ['public'];
            foreach (locales() as $lang) {
                if ($page->isPublished($lang) && $uri = $page->uri($lang)) {
                    Route::middleware($middleware)->prefix($uri)->name($lang.'::')->group(function (Router $router) {
                        $router->get('/', [PublicController::class, 'index'])->name('index-announcements');
                        $router->get('{slug}', [PublicController::class, 'show'])->name('announcement');
                    });
                }
            }
        }

        /*
         * Admin routes
         */
        Route::middleware('admin')->prefix('admin')->name('admin::')->group(function (Router $router) {
            $router->get('announcements', [AdminController::class, 'index'])->name('index-announcements')->middleware('can:read announcements');
            $router->get('announcements/export', [AdminController::class, 'export'])->name('admin::export-announcements')->middleware('can:read announcements');
            $router->get('announcements/create', [AdminController::class, 'create'])->name('create-announcement')->middleware('can:create announcements');
            $router->get('announcements/{announcement}/edit', [AdminController::class, 'edit'])->name('edit-announcement')->middleware('can:read announcements');
            $router->post('announcements', [AdminController::class, 'store'])->name('store-announcement')->middleware('can:create announcements');
            $router->put('announcements/{announcement}', [AdminController::class, 'update'])->name('update-announcement')->middleware('can:update announcements');
        });

        /*
         * API routes
         */
        Route::middleware(['api', 'auth:api'])->prefix('api')->group(function (Router $router) {
            $router->get('announcements', [ApiController::class, 'index'])->middleware('can:read announcements');
            $router->patch('announcements/{announcement}', [ApiController::class, 'updatePartial'])->middleware('can:update announcements');
            $router->delete('announcements/{announcement}', [ApiController::class, 'destroy'])->middleware('can:delete announcements');
        });
    }
}
