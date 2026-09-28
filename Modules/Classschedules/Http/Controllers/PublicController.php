<?php

namespace TypiCMS\Modules\Classschedules\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use TypiCMS\Modules\Announcements\Facades\Announcements;
use TypiCMS\Modules\Categories\Models\Category;
use TypiCMS\Modules\Classschedules\Models\AnnouncementDepartment;
use TypiCMS\Modules\Classschedules\Models\ClassScheduleItems;
use TypiCMS\Modules\Core\Http\Controllers\BasePublicController;
use TypiCMS\Modules\Classschedules\Models\Classschedule;
use TypiCMS\Modules\Departments\Models\Department;

class PublicController extends BasePublicController
{
    public function index(Request $request): View
    {
        $models = collect(new Classschedule);

        if ($request->ajax()) {
            $items = Classschedule::where('announcement_department_id', $request['department_id'])->where('year', $request['year'])->published()->order()->get();
            return view('classschedules::public._list', compact('items'));
        }
        $departments = AnnouncementDepartment::all();

        $announcements = Announcements::published()
            ->category(1)
            ->highlight()
            ->order()
            ->get();

        return view('classschedules::public.index')
            ->with(compact('models', 'departments', 'announcements'));
    }

    public function show($slug): View
    {
        $model = Classschedule::published()->whereSlugIs($slug)->firstOrFail();

        return view('classschedules::public.show')
            ->with(compact('model'));
    }
}
