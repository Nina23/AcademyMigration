<?php

namespace TypiCMS\Modules\Newsletters\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use TypiCMS\Modules\Core\Filters\FilterOr;
use TypiCMS\Modules\Core\Http\Controllers\BaseApiController;
use TypiCMS\Modules\Newsletters\Models\Newsletter;

class ApiController extends BaseApiController
{
    public function index(Request $request): LengthAwarePaginator
    {
        $data = QueryBuilder::for(Newsletter::class)
            ->selectFields($request->input('fields.newsletters'))
            ->allowedSorts(['status_translated', 'title_translated'])
            ->allowedFilters([
                AllowedFilter::custom('title', new FilterOr()),
            ])
            ->allowedIncludes(['image'])
            ->paginate($request->input('per_page'));

        return $data;
    }

    protected function updatePartial(Newsletter $newsletter, Request $request)
    {
        foreach ($request->only('status') as $key => $content) {
            if ($newsletter->isTranslatableAttribute($key)) {
                foreach ($content as $lang => $value) {
                    $newsletter->setTranslation($key, $lang, $value);
                }
            } else {
                $newsletter->{$key} = $content;
            }
        }

        $newsletter->save();
    }

    public function destroy(Newsletter $newsletter)
    {
        $newsletter->delete();
    }
}
