<?php

namespace TypiCMS\Modules\Pages\Http\Requests;

use TypiCMS\Modules\Core\Http\Requests\AbstractFormRequest;

class ContactRequest extends AbstractFormRequest
{
    public function rules()
    {
        $rules = [
            'email' => 'required|email|max:255',
            'name' => 'required|max:255',
            'subject' => 'required|max:255',
        ];

        return $rules;
    }
}
