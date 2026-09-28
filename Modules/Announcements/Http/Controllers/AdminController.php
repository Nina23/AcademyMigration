<?php

namespace TypiCMS\Modules\Announcements\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use TypiCMS\Modules\Core\Http\Controllers\BaseAdminController;
use TypiCMS\Modules\Announcements\Exports\Export;
use TypiCMS\Modules\Announcements\Http\Requests\FormRequest;
use TypiCMS\Modules\Announcements\Models\Announcement;
use TypiCMS\Modules\Announcements\Models\AnnouncementCategory;
use Carbon\Carbon;

class AdminController extends BaseAdminController
{
    public function index(): View
    {
        return view('announcements::admin.index');
    }

    public function export(Request $request)
    {
        $filename = date('Y-m-d') . ' ' . config('app.name') . ' announcements.xlsx';

        return Excel::download(new Export($request), $filename);
    }

    public function create(): View
    {
        $model = new Announcement();
        $categories = AnnouncementCategory::all()->pluck('title', 'id')->all();

        return view('announcements::admin.create')
            ->with(compact('model', 'categories'));
    }

    public function edit(announcement $announcement): View
    {
        $announcement->program = json_decode($announcement->program,true);
        $announcement->year = json_decode($announcement->year,true);
        $categories = AnnouncementCategory::all()->pluck('title', 'id')->all();
        
        return view('announcements::admin.edit')
            ->with(['model' => $announcement, 'categories' => $categories]);
    }

    public function store(FormRequest $request): RedirectResponse
    {
        if($request->validated()){
            $data = $request->validated();
            $data['program'] = json_encode($request->program);
        //    $data['year'] = json_encode($request->year);
        }
        
        $announcement = Announcement::create($data);

        return $this->redirect($request, $announcement);
    }

    public function update(announcement $announcement, FormRequest $request): RedirectResponse
    {
        $announcement->update($request->validated());

        return $this->redirect($request, $announcement);
    }
}
