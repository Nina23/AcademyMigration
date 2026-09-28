<?php

namespace TypiCMS\Modules\Biographies\Composers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Sidebar\SidebarGroup;
use Maatwebsite\Sidebar\SidebarItem;

class SidebarViewComposer
{
    public function compose(View $view)
    {
        if (Gate::denies('read biographies')) {
            return;
        }
        $view->sidebar->group(__('Content'), function (SidebarGroup $group) {
            $group->id = 'content';
            $group->weight = 30;
            $group->addItem(__('Biographies'), function (SidebarItem $item) {
                $item->id = 'biographies';
                $item->icon = config('typicms.biographies.sidebar.icon');
                $item->weight = config('typicms.biographies.sidebar.weight');
                $item->route('admin::index-biographies');
                $item->append('admin::create-biography');
            });
        });
    }
}
