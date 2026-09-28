<?php

namespace TypiCMS\Modules\Banners\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use TypiCMS\Modules\Core\Http\Controllers\BaseAdminController;
use TypiCMS\Modules\Banners\Exports\Export;
use TypiCMS\Modules\Banners\Http\Requests\FormRequest;
use TypiCMS\Modules\Banners\Models\Banner;

class AdminController extends BaseAdminController
{
    public function index(): View
    {
        return view('banners::admin.index');
    }

    public function export(Request $request)
    {
        $filename = date('Y-m-d').' '.config('app.name').' banners.xlsx';

        return Excel::download(new Export($request), $filename);
    }

    public function create(): View
    {
        $model = new Banner();

        return view('banners::admin.create')
            ->with(compact('model'));
    }

    public function edit(banner $banner): View
    {
        return view('banners::admin.edit')
            ->with(['model' => $banner]);
    }

    public function store(FormRequest $request): RedirectResponse
    {
        $banner = Banner::create($request->validated());

        return $this->redirect($request, $banner);
    }

    public function update(banner $banner, FormRequest $request): RedirectResponse
    {
        $banner->update($request->validated());

        return $this->redirect($request, $banner);
    }
}
