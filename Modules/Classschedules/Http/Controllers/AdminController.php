<?php

namespace TypiCMS\Modules\Classschedules\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use TypiCMS\Modules\Classschedules\Models\ClassScheduleItems;
use TypiCMS\Modules\Core\Http\Controllers\BaseAdminController;
use TypiCMS\Modules\Classschedules\Exports\Export;
use TypiCMS\Modules\Classschedules\Http\Requests\FormRequest;
use TypiCMS\Modules\Classschedules\Http\Requests\FormItemRequest;
use TypiCMS\Modules\Classschedules\Models\Classschedule;
use TypiCMS\Modules\Announcements\Models\AnnouncementCategory;
use TypiCMS\Modules\Classschedules\Models\AnnouncementDepartment;
use TypiCMS\Modules\Departments\Models\Department;

class AdminController extends BaseAdminController
{
    public function index(): View
    {
        return view('classschedules::admin.index');
    }

    public function export(Request $request)
    {
        $filename = date('Y-m-d').' '.config('app.name').' classschedules.xlsx';

        return Excel::download(new Export($request), $filename);
    }

    public function create(): View
    {
        $model = new Classschedule();
        $departments = $departments = AnnouncementDepartment::all()->pluck('title', 'id')->toArray();
        $announcementCategories = AnnouncementCategory::all()->pluck('title', 'id')->all();

        return view('classschedules::admin.create')
            ->with(compact('model', 'departments','announcementCategories'));
    }

    public function edit(classschedule $classschedule): View
    {
        $departments = AnnouncementDepartment::all()->pluck('title', 'id')->toArray();
        $announcementCategories = AnnouncementCategory::all()->pluck('title', 'id')->all();
        $items= ClassScheduleItems::where('day_id',1)->where('class_schedule_id', $classschedule->id)->get();
        $types=['1'=>__('Predavanja'), '2'=> __('Vjezbe'), '3'=>__('Ostalo')];
        return view('classschedules::admin.edit')
            ->with(['model' => $classschedule, 'departments'=> $departments, 'announcementCategories'=> $announcementCategories, 'items', 'items'=>$items, 'types'=>$types]);
    }

    public function store(FormRequest $request): RedirectResponse
    {
        if($request->validated()){
            $data = $request->validated();
        }
        $classschedule = Classschedule::create($data);
        return $this->redirect($request, $classschedule);
    }

    public function update(classschedule $classschedule, FormRequest $request): RedirectResponse
    {
        $classschedule->update($request->validated());
        return $this->redirect($request, $classschedule);
    }

    public function storeItem(FormItemRequest $request): RedirectResponse
    {
        if($request->validated()){
            $data = $request->validated();
        }
        $classschedule = ClassScheduleItems::create($data);

        return Redirect::back();
    }

    public function updateItem(ClassScheduleItems $item, FormItemRequest $request): RedirectResponse
    {
        $item->update($request->validated());
        return Redirect::back();
    }

    public function deleteItem(ClassScheduleItems $item)
    {
        $item->delete();
        return Redirect::back();
    }

    public function getModalDelete($id) {
        return view('classschedules::admin.modal_confirmation', compact('id'));
    }

    public function getModalEdit(ClassScheduleItems $item) {
        $types=['1'=>__('Predavanja'), '2'=> __('Vjezbe'), '3'=>__('Ostalo')];
        return view('classschedules::admin._modal_form', compact('item','types'));
    }

    public function getItems(Request $request){
        $items= ClassScheduleItems::where('day_id', $request['day_id'])->where('class_schedule_id',$request['schedule_id'])->get();
        if ($request->ajax()) {
            return view('classschedules::admin.items', compact('items'));
        }
    }
}
