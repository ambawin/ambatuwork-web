<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SprintReviewItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sprint_review_id' => $this->sprint_review_id,
            'backlog_item_id' => $this->backlog_item_id,
            'decision' => $this->decision,
            'notes' => $this->notes,
            'decider' => new UserResource($this->whenLoaded('decider') ?? $this->decider),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
