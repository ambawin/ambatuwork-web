<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ProjectInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
            'role' => Str::lower(trim((string) $this->input('role'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255'],
            'role' => ['required', 'in:member,supervisor'],
        ];
    }
}