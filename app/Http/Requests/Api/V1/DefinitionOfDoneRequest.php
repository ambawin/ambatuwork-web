<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class DefinitionOfDoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'checklist' => ['sometimes', 'array', 'min:1'],
            'checklist.*' => ['string', 'max:500'],
        ];
    }
}