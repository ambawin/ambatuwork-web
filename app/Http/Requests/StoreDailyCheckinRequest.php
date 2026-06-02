<?php

namespace App\Http\Requests;

use App\Models\DailyCheckin;
use Illuminate\Foundation\Http\FormRequest;

class StoreDailyCheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');
        return $this->user()->can('create', [DailyCheckin::class, $project]);
    }

    public function rules(): array
    {
        $sprint = $this->route('sprint');
        $sprintId = is_numeric($sprint) ? $sprint : $sprint->id;
        $userId = $this->user()->id;

        return [
            'yesterday' => ['nullable', 'string'],
            'today' => ['nullable', 'string'],
            'blockers' => ['nullable', 'string'],
            'confidence_score' => ['required', 'integer', 'between:1,5'],
            'checkin_date' => [
                'required',
                'date_format:Y-m-d',
                function ($attribute, $value, $fail) use ($sprintId, $userId) {
                    $exists = DailyCheckin::where('sprint_id', $sprintId)
                        ->where('user_id', $userId)
                        ->whereDate('checkin_date', $value)
                        ->exists();

                    if ($exists) {
                        $fail('The checkin date has already been taken.');
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('checkin_date')) {
            $this->merge([
                'checkin_date' => now()->toDateString(),
            ]);
        }
    }
}
