<?php

namespace TypiCMS\Modules\Classschedules\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laracasts\Presenter\PresentableTrait;
use Spatie\Translatable\HasTranslations;
use TypiCMS\Modules\Core\Models\Base;
use TypiCMS\Modules\Files\Models\File;
use TypiCMS\Modules\Files\Traits\HasFiles;
use TypiCMS\Modules\History\Traits\Historable;
use TypiCMS\Modules\Categories\Presenters\ModulePresenter;
use Illuminate\Database\Eloquent\Builder;
use TypiCMS\Modules\Classschedules\Models\Classschedule;

class AnnouncementDepartment extends Base
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
        'program_id',
        
    ];

    protected $guarded = [];

    public $translatable = [
        'title',
        'slug',
        'status'
    ];
    
    public function classSchedule()
    {
        return $this->hasMany(Classschedule::class);
    }

    public function getFormattedProgramAttribute(): string
    {
        if ($this->program_id == 1)
            return  __('Likovni program');
        if ($this->program_id == 2)
            return __('Muzicki program');
        if ($this->program_id == 3)
            return __('Dramski program');

        return '';
    }

}
