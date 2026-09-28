<?php

namespace TypiCMS\Modules\Classschedules\Http\Requests;

use TypiCMS\Modules\Core\Http\Requests\AbstractFormRequest;

class FormItemRequest extends AbstractFormRequest
{
    public function rules()
    {
        return [
            'title.*' => 'nullable|max:255',
            'professor.*' => 'nullable|max:255',
            'location.*' => 'nullable|max:255',
            'class_schedule_id' => 'nullable|integer',
            'from_date'=>'date',
            'to_date'=>'date',
            'type'=>'nullable|integer',
            'day_id' => 'nullable|integer',
            'position' => 'nullable|integer',
        ];
    }
}
