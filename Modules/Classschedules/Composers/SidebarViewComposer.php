<?php

namespace TypiCMS\Modules\Classschedules\Composers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Sidebar\SidebarGroup;
use Maatwebsite\Sidebar\SidebarItem;

class SidebarViewComposer
{
    public function compose(View $view)
    {
        if (Gate::denies('read classschedules')) {
            return;
        }
        $view->sidebar->group(__('Content'), function (SidebarGroup $group) {
            $group->id = 'content';
            $group->weight = 30;
            $group->addItem(__('Classschedules'), function (SidebarItem $item) {
                $item->id = 'classschedules';
                $item->icon = config('typicms.classschedules.sidebar.icon');
                $item->weight = config('typicms.classschedules.sidebar.weight');
                $item->route('admin::index-classschedules');
                $item->append('admin::create-classschedule');
            });
        });
    }
}
