<?php

namespace TypiCMS\Modules\Departments\Composers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Sidebar\SidebarGroup;
use Maatwebsite\Sidebar\SidebarItem;

class SidebarViewComposer
{
    public function compose(View $view)
    {
        if (Gate::denies('read departments')) {
            return;
        }
        $view->sidebar->group(__('Content'), function (SidebarGroup $group) {
            $group->id = 'content';
            $group->weight = 30;
            $group->addItem(__('Departments'), function (SidebarItem $item) {
                $item->id = 'departments';
                $item->icon = config('typicms.departments.sidebar.icon');
                $item->weight = config('typicms.departments.sidebar.weight');
                $item->route('admin::index-departments');
                $item->append('admin::create-department');
            });
        });
    }
}
