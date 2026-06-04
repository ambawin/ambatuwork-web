<?php

namespace App\Http\Requests;

use App\Models\Retrospective;
use Illuminate\Foundation\Http\FormRequest;

class StoreRetroItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if ($this->isMethod('post')) {
            return $this->user()->can('createItem', [Retrospective::class, $project]);
        }

        $retroItem = $this->route('retroItem');
        return $this->user()->can('updateItem', [Retrospective::class, $project, $retroItem]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:went_well,problem,idea,action'],
            'body' => ['required', 'string'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_sprint_id' => ['nullable', 'integer', 'exists:sprints,id'],
        ];
    }
}
