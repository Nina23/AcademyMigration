<?php

namespace TypiCMS\Modules\Classschedules\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use TypiCMS\Modules\Core\Facades\TypiCMS;
use TypiCMS\Modules\Classschedules\Http\Controllers\AdminController;
use TypiCMS\Modules\Classschedules\Http\Controllers\ApiController;
use TypiCMS\Modules\Classschedules\Http\Controllers\PublicController;

class RouteServiceProvider extends ServiceProvider
{
    public function map()
    {
        /*
         * Front office routes
         */
        if ($page = TypiCMS::getPageLinkedToModule('classschedules')) {
            $middleware = $page->private ? ['public', 'auth'] : ['public'];
            foreach (locales() as $lang) {
                if ($page->isPublished($lang) && $uri = $page->uri($lang)) {
                    Route::middleware($middleware)->prefix($uri)->name($lang.'::')->group(function (Router $router) {
                        $router->get('/', [PublicController::class, 'index'])->name('index-classschedules');
                        $router->get('{slug}', [PublicController::class, 'show'])->name('classschedule');
                    });
                }
            }
        }

        /*
         * Admin routes
         */
        Route::middleware('admin')->prefix('admin')->name('admin::')->group(function (Router $router) {
            $router->get('classschedules', [AdminController::class, 'index'])->name('index-classschedules')->middleware('can:read classschedules');
            $router->get('classschedules/export', [AdminController::class, 'export'])->name('admin::export-classschedules')->middleware('can:read classschedules');
            $router->get('classschedules/create', [AdminController::class, 'create'])->name('create-classschedule')->middleware('can:create classschedules');
            $router->get('classschedules/{classschedule}/edit', [AdminController::class, 'edit'])->name('edit-classschedule')->middleware('can:read classschedules');
            $router->post('classschedules', [AdminController::class, 'store'])->name('store-classschedule')->middleware('can:create classschedules');
            $router->post('class/schedules/item', [AdminController::class, 'storeItem'])->name('store-class-schedule-item')->middleware('can:create classschedules');
            $router->put('classschedules/{classschedule}', [AdminController::class, 'update'])->name('update-classschedule')->middleware('can:update classschedules');
            $router->put('class/schedules/item/{item}', [AdminController::class, 'updateItem'])->name('update-class-schedule-item')->middleware('can:update classschedules');
            $router->get('class/schedules/item', [AdminController::class, 'getItems'])->name('show-class-schedule-item')->middleware('can:read classschedules');
            $router->post('class/schedules/item/{item}', [AdminController::class, 'deleteItem'])->name('delete-class-schedule-item')->middleware('can:delete classschedules');
            $router->get('class/schedules/item/{item}/confirm-delete', [AdminController::class, 'getModalDelete'])->name('delete-class-schedule-item-confirm')->middleware('can:delete classschedules');
            $router->get('class/schedules/item/{item}/confirm-edit', [AdminController::class, 'getModalEdit'])->name('edit-class-schedule-item-confirm')->middleware('can:edit classschedules');
        
        });

        /*
         * API routes
         */
        Route::middleware(['api', 'auth:api'])->prefix('api')->group(function (Router $router) {
            $router->get('classschedules', [ApiController::class, 'index'])->middleware('can:read classschedules');
            $router->patch('classschedules/{classschedule}', [ApiController::class, 'updatePartial'])->middleware('can:update classschedules');
            $router->delete('classschedules/{classschedule}', [ApiController::class, 'destroy'])->middleware('can:delete classschedules');
        });
    }
}
