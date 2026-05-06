<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BacklogItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'priority_rank' => $this->priority_rank,
            'business_value' => $this->business_value,
            'estimate_points' => $this->estimate_points,
            'acceptance_criteria' => $this->acceptance_criteria,
            'created_by_user_id' => $this->created_by_user_id,
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'assigned_to_user' => $this->whenLoaded('assignedTo', fn () => new UserResource($this->assignedTo)),
            'done_at' => $this->done_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}