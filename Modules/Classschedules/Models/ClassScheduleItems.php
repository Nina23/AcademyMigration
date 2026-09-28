<?php

namespace TypiCMS\Modules\Classschedules\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laracasts\Presenter\PresentableTrait;
use Spatie\Translatable\HasTranslations;
use TypiCMS\Modules\Core\Models\Base;
use TypiCMS\Modules\History\Traits\Historable;
use TypiCMS\Modules\Classschedules\Presenters\ModulePresenter;
use TypiCMS\Modules\Classschedules\Models\Classschedule;


class ClassScheduleItems extends Base
{
    use HasTranslations;
    use Historable;
    use PresentableTrait;
    protected $presenter = ModulePresenter::class;
    protected $relations=['class_schedule_id'];

    protected $fillable = [
        'title',
        'class_schedule_id',
        'professor',
        'location',
        'from_date',
        'to_date',
        'type',
        'day_id',
        'position'
    ];

    protected $dates = ['from_date', 'to_date'];


    protected $guarded = [];

    public $translatable = [
        'title',
        'professor',
        'location',
    ];

    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(Classschedule::class, 'class_schedule_id');
    }

    public function scopeDay($query, $day)
    {
        return $query->where('day_id', $day);
    }

    public function getFormattedTypeAttribute()
    {
        if($this->type == 1)
            return __('Predavanja');
        if($this->type == 2)
            return __('Vjezbe');
        return __('Ostalo');
    }
}
