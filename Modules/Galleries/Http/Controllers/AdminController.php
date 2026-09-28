<?php

namespace TypiCMS\Modules\Galleries\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use TypiCMS\Modules\Core\Http\Controllers\BaseAdminController;
use TypiCMS\Modules\Galleries\Exports\Export;
use TypiCMS\Modules\Galleries\Http\Requests\FormRequest;
use TypiCMS\Modules\Galleries\Models\Gallery;

class AdminController extends BaseAdminController
{
    public function index(): View
    {
        return view('galleries::admin.index');
    }

    public function export(Request $request)
    {
        $filename = date('Y-m-d').' '.config('app.name').' galleries.xlsx';

        return Excel::download(new Export($request), $filename);
    }

    public function create(): View
    {
        $model = new Gallery();

        return view('galleries::admin.create')
            ->with(compact('model'));
    }

    public function edit(gallery $gallery): View
    {
        return view('galleries::admin.edit')
            ->with(['model' => $gallery]);
    }

    public function store(FormRequest $request): RedirectResponse
    {
        $gallery = Gallery::create($request->validated());

        return $this->redirect($request, $gallery);
    }

    public function update(gallery $gallery, FormRequest $request): RedirectResponse
    {
        $gallery->update($request->validated());

        return $this->redirect($request, $gallery);
    }
}
