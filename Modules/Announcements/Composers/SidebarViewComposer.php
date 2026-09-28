<?php

namespace TypiCMS\Modules\Announcements\Composers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Sidebar\SidebarGroup;
use Maatwebsite\Sidebar\SidebarItem;

class SidebarViewComposer
{
    public function compose(View $view)
    {
        if (Gate::denies('read announcements')) {
            return;
        }
        $view->sidebar->group(__('Content'), function (SidebarGroup $group) {
            $group->id = 'content';
            $group->weight = 30;
            $group->addItem(__('Announcements'), function (SidebarItem $item) {
                $item->id = 'announcements';
                $item->icon = config('typicms.announcements.sidebar.icon');
                $item->weight = config('typicms.announcements.sidebar.weight');
                $item->route('admin::index-announcements');
                $item->append('admin::create-announcement');
            });
        });
    }
}
