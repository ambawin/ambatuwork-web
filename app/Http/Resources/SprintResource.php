<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SprintResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $itemCount = $this->items_count ?? ($this->relationLoaded('items') ? $this->items->count() : null);

        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'name' => $this->name,
            'sprint_goal' => $this->sprint_goal,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'created_by_user_id' => $this->created_by_user_id,
            'closed_by_user_id' => $this->closed_by_user_id,
            'closed_at' => $this->closed_at,
            'item_count' => $itemCount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}