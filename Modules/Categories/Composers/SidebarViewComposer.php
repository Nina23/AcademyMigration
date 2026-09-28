<?php

namespace TypiCMS\Modules\Categories\Composers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Sidebar\SidebarGroup;
use Maatwebsite\Sidebar\SidebarItem;

class SidebarViewComposer
{
    public function compose(View $view)
    {
        if (Gate::denies('read categories')) {
            return;
        }
        $view->sidebar->group(__('Content'), function (SidebarGroup $group) {
            $group->id = 'content';
            $group->weight = 30;
            $group->addItem(__('Categories'), function (SidebarItem $item) {
                $item->id = 'categories';
                $item->icon = config('typicms.categories.sidebar.icon');
                $item->weight = config('typicms.categories.sidebar.weight');
                $item->route('admin::index-categories');
                $item->append('admin::create-category');
            });
        });
    }
}
