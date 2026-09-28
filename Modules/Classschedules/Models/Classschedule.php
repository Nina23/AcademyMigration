<?php

namespace TypiCMS\Modules\Classschedules\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laracasts\Presenter\PresentableTrait;
use Spatie\Translatable\HasTranslations;
use TypiCMS\Modules\Core\Models\Base;
use TypiCMS\Modules\Files\Models\File;
use TypiCMS\Modules\Files\Traits\HasFiles;
use TypiCMS\Modules\History\Traits\Historable;
use TypiCMS\Modules\Classschedules\Presenters\ModulePresenter;
use TypiCMS\Modules\Announcements\Models\AnnouncementCategory;
use TypiCMS\Modules\Departments\Models\Department;

class Classschedule extends Base
{
    use HasFiles;
    use HasTranslations;
    use Historable;
    use PresentableTrait;

    protected $presenter = ModulePresenter::class;
    protected $fillable = [
        'title',
        'slug',
        'status',
        'summary',
        'body',
        'year',
        'announcement_category_id',
        'announcement_department_id',
        'professor',
        'location',
        'from_date',
        'to_date',
        'expiry',
        'day_id',
        'position'
    ];

    protected $dates = ['from_date', 'to_date', 'expiry'];


    protected $guarded = [];

    public $translatable = [
        'title',
        'slug',
        'status',
        'summary',
        'body',
        'professor',
        'location',
    ];

    public function getThumbAttribute(): string
    {
        return $this->present()->image(null, 54);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id');
    }

    public function announcementCategory(): BelongsTo
    {
        return $this->belongsTo(AnnouncementCategory::class, 'announcement_category_id');
    }

    public function announcementDepartment(): BelongsTo
    {
        return $this->belongsTo(AnnouncementDepartment::class, 'announcement_department_id');
    }

    public function classScheduleItems(): HasMany
    {
        return $this->hasMany(ClassScheduleItems::class, 'class_schedule_id', 'id');
    }

    public function getDepartmentAttribute(): string
    {
        return $this->announcementDepartment->title;
    }

    public function getFormattedYearAttribute(): string
    {
        if ($this->year == 1)
            return __('I');
        if ($this->year == 2)
            return __('II');
        if ($this->year == 3)
            return __('III');
        if ($this->year == 4)
            return __('IV');
        if ($this->year == 5)
            return __('MR');
        if ($this->year == 6)
            return __('DR');
        return '';
    }
}
