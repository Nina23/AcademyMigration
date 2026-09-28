<?php

namespace TypiCMS\Modules\Galleries\Composers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Sidebar\SidebarGroup;
use Maatwebsite\Sidebar\SidebarItem;

class SidebarViewComposer
{
    public function compose(View $view)
    {
        if (Gate::denies('read galleries')) {
            return;
        }
        $view->sidebar->group(__('Content'), function (SidebarGroup $group) {
            $group->id = 'content';
            $group->weight = 30;
            $group->addItem(__('Galleries'), function (SidebarItem $item) {
                $item->id = 'galleries';
                $item->icon = config('typicms.galleries.sidebar.icon');
                $item->weight = config('typicms.galleries.sidebar.weight');
                $item->route('admin::index-galleries');
                $item->append('admin::create-gallery');
            });
        });
    }
}
