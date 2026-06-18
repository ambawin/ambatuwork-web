<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ProjectUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'product_goal' => ['sometimes', 'string', 'max:5000'],
            'default_sprint_length_days' => ['sometimes', 'integer', 'min:1', 'max:30'],
            'status' => ['sometimes', 'in:active,archived'],
        ];
    }
}