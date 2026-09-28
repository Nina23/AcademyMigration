<?php

namespace TypiCMS\Modules\Biographies\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use TypiCMS\Modules\Core\Http\Controllers\BaseAdminController;
use TypiCMS\Modules\Biographies\Exports\Export;
use TypiCMS\Modules\Biographies\Http\Requests\FormRequest;
use TypiCMS\Modules\Biographies\Models\Biography;

class AdminController extends BaseAdminController
{
    public function index(): View
    {
        return view('biographies::admin.index');
    }

    public function export(Request $request)
    {
        $filename = date('Y-m-d').' '.config('app.name').' biographies.xlsx';

        return Excel::download(new Export($request), $filename);
    }

    public function create(): View
    {
        $model = new Biography();

        return view('biographies::admin.create')
            ->with(compact('model'));
    }

    public function edit(biography $biography): View
    {
        return view('biographies::admin.edit')
            ->with(['model' => $biography]);
    }

    public function store(FormRequest $request): RedirectResponse
    {
        $biography = Biography::create($request->validated());

        return $this->redirect($request, $biography);
    }

    public function update(biography $biography, FormRequest $request): RedirectResponse
    {
        $biography->update($request->validated());

        return $this->redirect($request, $biography);
    }
}
