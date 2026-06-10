<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectBacklogItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['sometimes', 'string', Rule::in(['story', 'task', 'bug', 'improvement'])],
            'priority' => ['nullable', 'string', Rule::in(['highest', 'high', 'medium', 'low', 'lowest'])],
            'estimate_points' => ['nullable', 'integer', 'min:1', 'max:100'],
            'acceptance_criteria' => ['nullable', 'array'],
            'acceptance_criteria.*' => ['string', 'max:1000'],
            'assigned_to_user_id' => [
                'nullable',
                'integer',
                Rule::exists('project_memberships', 'user_id')->where(function ($query) use ($project): void {
                    $query->where('project_id', $project->id)
                        ->where('status', 'active');
                }),
            ],
        ];
    }
}