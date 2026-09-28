<?php

namespace TypiCMS\Modules\Departments\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use TypiCMS\Modules\Core\Http\Controllers\BaseAdminController;
use TypiCMS\Modules\Departments\Exports\Export;
use TypiCMS\Modules\Departments\Http\Requests\FormRequest;
use TypiCMS\Modules\Departments\Models\Department;

class AdminController extends BaseAdminController
{
    public function index(): View
    {
        return view('departments::admin.index');
    }

    public function export(Request $request)
    {
        $filename = date('Y-m-d').' '.config('app.name').' departments.xlsx';

        return Excel::download(new Export($request), $filename);
    }

    public function create(): View
    {
        $model = new Department();

        return view('departments::admin.create')
            ->with(compact('model'));
    }

    public function edit(department $department): View
    {
        return view('departments::admin.edit')
            ->with(['model' => $department]);
    }

    public function store(FormRequest $request): RedirectResponse
    {
        $department = Department::create($request->validated());

        return $this->redirect($request, $department);
    }

    public function update(department $department, FormRequest $request): RedirectResponse
    {
        $department->update($request->validated());

        return $this->redirect($request, $department);
    }
}
