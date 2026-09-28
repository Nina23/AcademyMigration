<?php

namespace TypiCMS\Modules\Announcements\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use TypiCMS\Modules\Core\Filters\FilterOr;
use TypiCMS\Modules\Core\Http\Controllers\BaseApiController;
use TypiCMS\Modules\Announcements\Models\Announcement;

class ApiController extends BaseApiController
{
    public function index(Request $request): LengthAwarePaginator
    {
        $data = QueryBuilder::for(Announcement::class)
            ->selectFields($request->input('fields.announcements'))
            ->allowedSorts(['status_translated', 'title_translated'])
            ->allowedFilters([
                AllowedFilter::custom('title', new FilterOr()),
            ])
            ->allowedIncludes(['image'])
            ->paginate($request->input('per_page'));

        return $data;
    }

    protected function updatePartial(Announcement $announcement, Request $request)
    {
        foreach ($request->only('status') as $key => $content) {
            if ($announcement->isTranslatableAttribute($key)) {
                foreach ($content as $lang => $value) {
                    $announcement->setTranslation($key, $lang, $value);
                }
            } else {
                $announcement->{$key} = $content;
            }
        }

        $announcement->save();
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
    }
}
