<?php

namespace TypiCMS\Modules\Advertismentboards\Composers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Sidebar\SidebarGroup;
use Maatwebsite\Sidebar\SidebarItem;

class SidebarViewComposer
{
    public function compose(View $view)
    {
        if (Gate::denies('read advertismentboards')) {
            return;
        }
        $view->sidebar->group(__('Content'), function (SidebarGroup $group) {
            $group->id = 'content';
            $group->weight = 30;
            $group->addItem(__('Advertisment board'), function (SidebarItem $item) {
                $item->id = 'advertismentboards';
                $item->icon = config('typicms.advertismentboards.sidebar.icon');
                $item->weight = config('typicms.advertismentboards.sidebar.weight');
                $item->route('admin::index-advertismentboards');
                $item->append('admin::create-advertismentboard');
            });
        });
    }
}
