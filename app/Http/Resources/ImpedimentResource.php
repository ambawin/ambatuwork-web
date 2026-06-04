<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImpedimentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'sprint_id' => $this->sprint_id,
            'backlog_item_id' => $this->backlog_item_id,
            'reporter' => new UserResource($this->whenLoaded('reporter') ?? $this->reporter),
            'owner' => new UserResource($this->whenLoaded('owner') ?? $this->owner),
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'resolved_at' => $this->resolved_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
