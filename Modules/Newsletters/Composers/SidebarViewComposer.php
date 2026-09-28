<?php

namespace TypiCMS\Modules\Newsletters\Composers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Sidebar\SidebarGroup;
use Maatwebsite\Sidebar\SidebarItem;

class SidebarViewComposer
{
    public function compose(View $view)
    {
        if (Gate::denies('read newsletters')) {
            return;
        }
        $view->sidebar->group(__('Content'), function (SidebarGroup $group) {
            $group->id = 'content';
            $group->weight = 30;
            $group->addItem(__('Newsletters'), function (SidebarItem $item) {
                $item->id = 'newsletters';
                $item->icon = config('typicms.newsletters.sidebar.icon');
                $item->weight = config('typicms.newsletters.sidebar.weight');
                $item->route('admin::index-newsletters');
                $item->append('admin::create-newsletter');
            });
        });
    }
}
