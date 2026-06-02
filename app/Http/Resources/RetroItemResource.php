<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RetroItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'retrospective_id' => $this->retrospective_id,
            'author' => new UserResource($this->whenLoaded('author') ?? $this->author),
            'type' => $this->type,
            'body' => $this->body,
            'assigned_to' => new UserResource($this->whenLoaded('assignee') ?? $this->assignee),
            'due_sprint_id' => $this->due_sprint_id,
            'is_completed' => $this->is_completed,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
