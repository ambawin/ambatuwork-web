<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ProjectMemberUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'role' => Str::lower(trim((string) $this->input('role'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'in:member,supervisor'],
        ];
    }
}