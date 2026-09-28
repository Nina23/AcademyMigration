<?php

namespace TypiCMS\Modules\Announcements\Http\Controllers;

use Illuminate\View\View;
use TypiCMS\Modules\Core\Http\Controllers\BasePublicController;
use TypiCMS\Modules\Announcements\Models\Announcement;
use TypiCMS\Modules\Announcements\Models\AnnouncementCategory;
use TypiCMS\Modules\Tags\Models\Tag;
use Illuminate\Http\Request;

class PublicController extends BasePublicController
{
    public function index(Request $request): View
    {
        $models = Announcement::published()
            ->filterByTag($request['tag_id'] ?? 0)
            ->filterByCategory($request['category_id'] ?? 0)
            ->filterByProgram($request['program_id'] ?? "3")
            ->IsExpiry()
            ->order()
            ->with('image')
            ->paginate(config('typicms.news.per_page'));

        $schedules = Announcement::published()
            ->highlight()
            ->take(10)
            ->IsExpiry()
            ->get();
        $categories = AnnouncementCategory::all();
        $tags = Tag::withCount('announcement')->get();
        $countAnnouncement = Announcement::published()->IsExpiry()->count();
        $countFineArtsAnnouncement = Announcement::published()
            ->IsExpiry()
            ->filterByProgram("0")
            ->count();

        $countMusicArtsAnnouncement = Announcement::published()
            ->IsExpiry()
            ->filterByProgram("1")
            ->count();

        $countDramaticArtsAnnouncement = Announcement::published()
            ->IsExpiry()
            ->filterByProgram("2")
            ->count();
        if ($request->ajax()) {
            return view('announcements::public._list', compact('models'));
        }


        return view('announcements::public.index')
            ->with(compact('models', 'schedules', 'categories', 'tags', 'countAnnouncement', 'countFineArtsAnnouncement', 'countMusicArtsAnnouncement', 'countDramaticArtsAnnouncement'));
    }

    public function show($slug): View
    {
        $model = Announcement::published()->whereSlugIs($slug)->firstOrFail();

        return view('announcements::public.show')
            ->with(compact('model'));
    }
}
