<?php

namespace App\Http\Requests;

use App\Models\PeerReviewCycle;
use Illuminate\Foundation\Http\FormRequest;

class StorePeerReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');
        return $this->user()->can('submitReview', [PeerReviewCycle::class, $project]);
    }

    public function rules(): array
    {
        return [
            'reviewee_user_id' => [
                'required',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value === $this->user()->id) {
                        $fail('You cannot submit a peer review for yourself.');
                    }
                },
            ],
            'collaboration_score' => ['required', 'integer', 'between:1,5'],
            'delivery_score' => ['required', 'integer', 'between:1,5'],
            'communication_score' => ['required', 'integer', 'between:1,5'],
            'continue_feedback' => ['nullable', 'string'],
            'improve_feedback' => ['nullable', 'string'],
            'is_anonymous_to_reviewee' => ['nullable', 'boolean'],
        ];
    }
}
