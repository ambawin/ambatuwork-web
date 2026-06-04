<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyCheckinResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'sprint_id' => $this->sprint_id,
            'user' => new UserResource($this->whenLoaded('user') ?? $this->user),
            'checkin_date' => $this->checkin_date->toDateString(),
            'yesterday' => $this->yesterday,
            'today' => $this->today,
            'blockers' => $this->blockers,
            'confidence_score' => $this->confidence_score,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
