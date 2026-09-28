<?php

namespace TypiCMS\Modules\Announcements\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laracasts\Presenter\PresentableTrait;
use Spatie\Translatable\HasTranslations;
use TypiCMS\Modules\Core\Models\Base;
use TypiCMS\Modules\Files\Models\File;
use TypiCMS\Modules\Files\Traits\HasFiles;
use TypiCMS\Modules\History\Traits\Historable;
use TypiCMS\Modules\Categories\Presenters\ModulePresenter;
use Illuminate\Database\Eloquent\Builder;
use TypiCMS\Modules\Announcements\Models\Announcement;
use TypiCMS\Modules\Exams\Models\Exam;

class AnnouncementCategory extends Base
{
    use HasFiles;
    use HasTranslations;
    use Historable;
    use PresentableTrait;

    protected $presenter = ModulePresenter::class;

    protected $guarded = [];

    public $translatable = [
        'title',
        'slug',
        'status'
    ];
    
    public function announcement()
    {
        return $this->hasMany(Announcement::class);
    }

    public function exam()
    {
        return $this->hasMany(Exam::class);
    }

}
