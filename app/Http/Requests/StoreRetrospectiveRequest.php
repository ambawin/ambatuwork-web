<?php

namespace App\Http\Requests;

use App\Models\Retrospective;
use Illuminate\Foundation\Http\FormRequest;

class StoreRetrospectiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');
        return $this->user()->can('create', [Retrospective::class, $project]);
    }

    public function rules(): array
    {
        return [
            'team_happiness_score' => ['nullable', 'integer', 'between:1,5'],
        ];
    }
}
