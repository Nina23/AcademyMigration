<?php

namespace TypiCMS\Modules\Announcements\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laracasts\Presenter\PresentableTrait;
use Spatie\Translatable\HasTranslations;
use TypiCMS\Modules\Core\Models\Base;
use TypiCMS\Modules\Files\Models\File;
use TypiCMS\Modules\Files\Traits\HasFiles;
use TypiCMS\Modules\History\Traits\Historable;
use TypiCMS\Modules\Announcements\Presenters\ModulePresenter;
use TypiCMS\Modules\Tags\Traits\HasTags;

class Announcement extends Base
{
    use HasFiles;
    use HasTranslations;
    use Historable;
    use PresentableTrait;
    use HasTags;

    protected $presenter = ModulePresenter::class;

    protected $fillable = [
        //program i year prave problem
        'title', 'slug',
        'status',
        'summary',
        'body',
        'professor',
        'location', 'program', 'year', 'highlight', 'announcement_category_id', 'date', 'expiry'
    ];

    protected $dates = ['date', 'expiry'];

    public $translatable = [
        'title',
        'slug',
        'status',
        'summary',
        'body',
        'professor',
        'location',
        'highlight'
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

    public function scopeCategory($query, $category_id)
    {
        return $query->where('announcement_category_id', $category_id);
    }

    public function scopeHighlight(Builder $query): Builder
    {
        if (request('preview')) {
            return $query;
        }
        $field = 'highlight';
        if (in_array($field, (array)$this->translatable)) {
            $field .= '->' . config('app.locale');
        }

        return $query->where($field, '1');
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

    public function getFormattedProgramAttribute(): string
    {
        $formattedProgram = '';
        $programs = json_decode($this->program, true);
        $lastKey = array_key_last($programs);
        foreach ($programs as $key => $program) {
            if ($program == 0) {
                $formattedProgram .= __('Likovni program');
            }
            if ($program == 1) {
                $formattedProgram .= __('Muzicki program');
            }
            if ($program == 2) {
                $formattedProgram .= __('Dramski program');
            }
            if ($lastKey != $key) {
                $formattedProgram .= ', ';
            }
        }
        return $formattedProgram;
    }

    public function getFormattedProgramOutputAttribute():array
    {
        return  json_decode($this->program, true);
    }

    public function scopeIsExpiry(Builder $query): Builder
    {
        return $query->whereDate('expiry', '>', now());
    }

    public function scopeFilterByTag(Builder $query,$tag_id): Builder
    {
        if ($tag_id==0) {
            return $query;
        }
        return $query->whereHas('tags', function ($q) use ($tag_id) {
            return $q->where('tags.id', $tag_id);
        });
    }

    public function scopefilterByCategory(Builder $query, $category_id): Builder
    {
        if ($category_id==0) {
            return $query;
        }
        return $query->where('announcement_category_id', $category_id);
    }

    public function scopeFilterByProgram(Builder $query, $program_id): Builder
    {
        if ($program_id==3) {
            return $query;
        } 
        return $query->whereJsonContains('program', $program_id);
    }
}
