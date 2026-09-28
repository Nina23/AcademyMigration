<?php

namespace TypiCMS\Modules\Departments\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use TypiCMS\Modules\Core\Filters\FilterOr;
use TypiCMS\Modules\Core\Http\Controllers\BaseApiController;
use TypiCMS\Modules\Departments\Models\Department;

class ApiController extends BaseApiController
{
    public function index(Request $request): LengthAwarePaginator
    {
        $data = QueryBuilder::for(Department::class)
            ->selectFields($request->input('fields.departments'))
            ->allowedSorts(['status_translated', 'title_translated'])
            ->allowedFilters([
                AllowedFilter::custom('title', new FilterOr()),
            ])
            ->allowedIncludes(['image'])
            ->paginate($request->input('per_page'));

        return $data;
    }

    protected function updatePartial(Department $department, Request $request)
    {
        foreach ($request->only('status') as $key => $content) {
            if ($department->isTranslatableAttribute($key)) {
                foreach ($content as $lang => $value) {
                    $department->setTranslation($key, $lang, $value);
                }
            } else {
                $department->{$key} = $content;
            }
        }

        $department->save();
    }

    public function destroy(Department $department)
    {
        $department->delete();
    }
}
