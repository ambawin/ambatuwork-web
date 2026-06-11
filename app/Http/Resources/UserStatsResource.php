<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'projects' => [
                'total_active' => (int) ($this['projects']['total_active'] ?? 0),
            ],
            'backlog_items' => [
                'assigned_total' => (int) ($this['backlog_items']['assigned_total'] ?? 0),
                'assigned_by_status' => [
                    'backlog' => (int) ($this['backlog_items']['assigned_by_status']['backlog'] ?? 0),
                    'ready' => (int) ($this['backlog_items']['assigned_by_status']['ready'] ?? 0),
                    'selected' => (int) ($this['backlog_items']['assigned_by_status']['selected'] ?? 0),
                    'in_progress' => (int) ($this['backlog_items']['assigned_by_status']['in_progress'] ?? 0),
                    'in_review' => (int) ($this['backlog_items']['assigned_by_status']['in_review'] ?? 0),
                    'done' => (int) ($this['backlog_items']['assigned_by_status']['done'] ?? 0),
                ],
                'completed_points' => (int) ($this['backlog_items']['completed_points'] ?? 0),
            ],
            'daily_checkins' => [
                'total_submitted' => (int) ($this['daily_checkins']['total_submitted'] ?? 0),
                'average_confidence' => (float) ($this['daily_checkins']['average_confidence'] ?? 0.0),
            ],
            'impediments' => [
                'reported_total' => (int) ($this['impediments']['reported_total'] ?? 0),
                'reported_resolved' => (int) ($this['impediments']['reported_resolved'] ?? 0),
                'reported_by_status' => [
                    'open' => (int) ($this['impediments']['reported_by_status']['open'] ?? 0),
                    'in_progress' => (int) ($this['impediments']['reported_by_status']['in_progress'] ?? 0),
                    'resolved' => (int) ($this['impediments']['reported_by_status']['resolved'] ?? 0),
                    'ignored' => (int) ($this['impediments']['reported_by_status']['ignored'] ?? 0),
                ],
            ],
            'peer_reviews' => [
                'submitted_total' => (int) ($this['peer_reviews']['submitted_total'] ?? 0),
                'received_total' => (int) ($this['peer_reviews']['received_total'] ?? 0),
                'received_average_scores' => [
                    'collaboration' => (float) ($this['peer_reviews']['received_average_scores']['collaboration'] ?? 0.0),
                    'delivery' => (float) ($this['peer_reviews']['received_average_scores']['delivery'] ?? 0.0),
                    'communication' => (float) ($this['peer_reviews']['received_average_scores']['communication'] ?? 0.0),
                ],
            ],
        ];
    }
}
