<?php

namespace App\Http\Requests;

use App\Models\SprintReview;
use Illuminate\Foundation\Http\FormRequest;

class StoreSprintReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');
        return $this->user()->can('create', [SprintReview::class, $project]);
    }

    public function rules(): array
    {
        return [
            'summary' => ['nullable', 'string'],
            'demo_url' => ['nullable', 'string', 'url'],
            'items' => ['required', 'array'],
            'items.*.backlog_item_id' => ['required', 'integer', 'exists:backlog_items,id'],
            'items.*.decision' => ['required', 'string', 'in:accepted,rejected,carry_over'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
