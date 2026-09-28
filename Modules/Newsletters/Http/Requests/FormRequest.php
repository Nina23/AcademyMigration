<?php

namespace TypiCMS\Modules\Newsletters\Http\Requests;

use TypiCMS\Modules\Core\Http\Requests\AbstractFormRequest;

class FormRequest extends AbstractFormRequest
{
    public function rules()
    {
        return [
            'email' => 'required|max:255',
            'name' => 'required|max:255',
            'type' => 'required'
        ];
    }
}
