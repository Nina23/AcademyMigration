<?php

namespace TypiCMS\Modules\Newsletters\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use TypiCMS\Modules\Core\Http\Controllers\BaseAdminController;
use TypiCMS\Modules\Newsletters\Exports\Export;
use TypiCMS\Modules\Newsletters\Http\Requests\FormRequest;
use TypiCMS\Modules\Newsletters\Models\Newsletter;
use Illuminate\Support\Facades\Log;

class AdminController extends BaseAdminController
{
    public function index(): View
    {
        return view('newsletters::admin.index');
    }

    public function export(Request $request)
    {
        $filename = date('Y-m-d').' '.config('app.name').' newsletters.xlsx';

        return Excel::download(new Export($request), $filename);
    }

    public function create(): View
    {
        $model = new Newsletter();

        return view('newsletters::admin.create')
            ->with(compact('model'));
    }

    public function edit(newsletter $newsletter): View
    {
        return view('newsletters::admin.edit')
            ->with(['model' => $newsletter]);
    }

    public function store(FormRequest $request): RedirectResponse
    {

        $newsletter = Newsletter::create($request->validated());

        return $this->redirect($request, $newsletter);
    }

    public function update(newsletter $newsletter, FormRequest $request): RedirectResponse
    {
        $newsletter->update($request->validated());

        Log::info($message);
        return $this->redirect($request, $newsletter);
    }
}
