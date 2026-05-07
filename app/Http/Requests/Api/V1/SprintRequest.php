<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'name' => ['required', 'string', 'max:255'],
            'sprint_goal' => ['required', 'string', 'max:5000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'backlog_item_ids' => ['required', 'array', 'min:1'],
            'backlog_item_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('backlog_items', 'id')->where(function ($query) use ($project): void {
                    $query->where('project_id', $project->id)
                        ->where('status', '!=', 'archived');
                }),
            ],
        ];
    }
}