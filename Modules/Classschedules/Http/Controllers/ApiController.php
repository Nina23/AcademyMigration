<?php

namespace TypiCMS\Modules\Classschedules\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use TypiCMS\Modules\Core\Filters\FilterOr;
use TypiCMS\Modules\Core\Http\Controllers\BaseApiController;
use TypiCMS\Modules\Classschedules\Models\Classschedule;

class ApiController extends BaseApiController
{
    public function index(Request $request): LengthAwarePaginator
    {
        $data = QueryBuilder::for(Classschedule::class)
            ->selectFields($request->input('fields.classschedules'))
            ->allowedSorts(['status_translated', 'title_translated'])
            ->allowedFilters([
                AllowedFilter::custom('title', new FilterOr()),
            ])
            ->allowedIncludes(['image'])
            ->paginate($request->input('per_page'));

        return $data;
    }

    protected function updatePartial(Classschedule $classschedule, Request $request)
    {
        foreach ($request->only('status') as $key => $content) {
            if ($classschedule->isTranslatableAttribute($key)) {
                foreach ($content as $lang => $value) {
                    $classschedule->setTranslation($key, $lang, $value);
                }
            } else {
                $classschedule->{$key} = $content;
            }
        }

        $classschedule->save();
    }

    public function destroy(Classschedule $classschedule)
    {
        $classschedule->delete();
    }
}
