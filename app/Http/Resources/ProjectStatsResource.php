<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'project' => [
                'id' => (int) ($this['project']['id'] ?? 0),
                'name' => (string) ($this['project']['name'] ?? ''),
                'status' => (string) ($this['project']['status'] ?? ''),
            ],
            'members' => [
                'total' => (int) ($this['members']['total'] ?? 0),
                'by_role' => [
                    'owner' => (int) ($this['members']['by_role']['owner'] ?? 0),
                    'member' => (int) ($this['members']['by_role']['member'] ?? 0),
                    'supervisor' => (int) ($this['members']['by_role']['supervisor'] ?? 0),
                ],
            ],
            'sprints' => [
                'total' => (int) ($this['sprints']['total'] ?? 0),
                'active' => (int) ($this['sprints']['active'] ?? 0),
                'completed' => (int) ($this['sprints']['completed'] ?? 0),
                'average_velocity' => (float) ($this['sprints']['average_velocity'] ?? 0.0),
            ],
            'backlog_items' => [
                'total' => (int) ($this['backlog_items']['total'] ?? 0),
                'total_points' => (int) ($this['backlog_items']['total_points'] ?? 0),
                'completed_points' => (int) ($this['backlog_items']['completed_points'] ?? 0),
                'by_status' => [
                    'backlog' => (int) ($this['backlog_items']['by_status']['backlog'] ?? 0),
                    'ready' => (int) ($this['backlog_items']['by_status']['ready'] ?? 0),
                    'selected' => (int) ($this['backlog_items']['by_status']['selected'] ?? 0),
                    'in_progress' => (int) ($this['backlog_items']['by_status']['in_progress'] ?? 0),
                    'in_review' => (int) ($this['backlog_items']['by_status']['in_review'] ?? 0),
                    'done' => (int) ($this['backlog_items']['by_status']['done'] ?? 0),
                ],
            ],
            'daily_checkins' => [
                'total_submitted' => (int) ($this['daily_checkins']['total_submitted'] ?? 0),
                'average_confidence' => (float) ($this['daily_checkins']['average_confidence'] ?? 0.0),
            ],
            'impediments' => [
                'total' => (int) ($this['impediments']['total'] ?? 0),
                'resolved' => (int) ($this['impediments']['resolved'] ?? 0),
                'by_status' => [
                    'open' => (int) ($this['impediments']['by_status']['open'] ?? 0),
                    'in_progress' => (int) ($this['impediments']['by_status']['in_progress'] ?? 0),
                    'resolved' => (int) ($this['impediments']['by_status']['resolved'] ?? 0),
                    'ignored' => (int) ($this['impediments']['by_status']['ignored'] ?? 0),
                ],
            ],
            'retrospectives' => [
                'total' => (int) ($this['retrospectives']['total'] ?? 0),
                'average_happiness_score' => (float) ($this['retrospectives']['average_happiness_score'] ?? 0.0),
            ],
            'peer_reviews' => [
                'total_cycles' => (int) ($this['peer_reviews']['total_cycles'] ?? 0),
                'average_scores' => [
                    'collaboration' => (float) ($this['peer_reviews']['average_scores']['collaboration'] ?? 0.0),
                    'delivery' => (float) ($this['peer_reviews']['average_scores']['delivery'] ?? 0.0),
                    'communication' => (float) ($this['peer_reviews']['average_scores']['communication'] ?? 0.0),
                ],
            ],
        ];
    }
}
