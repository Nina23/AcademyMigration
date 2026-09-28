<?php

namespace TypiCMS\Modules\Advertismentboards\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use TypiCMS\Modules\Core\Http\Controllers\BaseAdminController;
use TypiCMS\Modules\Advertismentboards\Exports\Export;
use TypiCMS\Modules\Advertismentboards\Http\Requests\FormRequest;
use TypiCMS\Modules\Advertismentboards\Models\Advertismentboard;

class AdminController extends BaseAdminController
{
    public function index(): View
    {
        return view('advertismentboards::admin.index');
    }

    public function export(Request $request)
    {
        $filename = date('Y-m-d').' '.config('app.name').' advertismentboards.xlsx';

        return Excel::download(new Export($request), $filename);
    }

    public function create(): View
    {
        $model = new Advertismentboard();

        return view('advertismentboards::admin.create')
            ->with(compact('model'));
    }

    public function edit(advertismentboard $advertismentboard): View
    {
        return view('advertismentboards::admin.edit')
            ->with(['model' => $advertismentboard]);
    }

    public function store(FormRequest $request): RedirectResponse
    {
        $advertismentboard = Advertismentboard::create($request->validated());

        return $this->redirect($request, $advertismentboard);
    }

    public function update(advertismentboard $advertismentboard, FormRequest $request): RedirectResponse
    {
        $advertismentboard->update($request->validated());

        return $this->redirect($request, $advertismentboard);
    }
}
