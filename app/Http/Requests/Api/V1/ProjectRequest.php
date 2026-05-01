<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'product_goal' => ['required', 'string', 'max:5000'],
            'default_sprint_length_days' => ['required', 'integer', 'min:1', 'max:30'],
            'wip_limit_per_member' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}