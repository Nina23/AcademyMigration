<?php

namespace TypiCMS\Modules\Categories\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use TypiCMS\Modules\Core\Http\Controllers\BaseAdminController;
use TypiCMS\Modules\Categories\Exports\Export;
use TypiCMS\Modules\Categories\Http\Requests\FormRequest;
use TypiCMS\Modules\Categories\Models\Category;

class AdminController extends BaseAdminController
{
    public function index(): View
    {
        return view('categories::admin.index');
    }

    public function export(Request $request)
    {
        $filename = date('Y-m-d').' '.config('app.name').' categories.xlsx';

        return Excel::download(new Export($request), $filename);
    }

    public function create(): View
    {
        $model = new Category();

        return view('categories::admin.create')
            ->with(compact('model'));
    }

    public function edit(category $category): View
    {
        return view('categories::admin.edit')
            ->with(['model' => $category]);
    }

    public function store(FormRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        return $this->redirect($request, $category);
    }

    public function update(category $category, FormRequest $request): RedirectResponse
    {
        $category->update($request->validated());

        return $this->redirect($request, $category);
    }
}
