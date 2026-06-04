<?php

namespace App\Http\Requests;

use App\Models\Impediment;
use Illuminate\Foundation\Http\FormRequest;

class StoreImpedimentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if ($this->isMethod('post')) {
            return $this->user()->can('create', [Impediment::class, $project]);
        }

        return $this->user()->can('update', [Impediment::class, $project]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'backlog_item_id' => ['nullable', 'integer', 'exists:backlog_items,id'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'string', 'in:open,in_progress,resolved,ignored'],
        ];
    }
}
