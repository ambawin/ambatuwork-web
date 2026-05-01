<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'product_goal' => $this->product_goal,
            'owner_user_id' => $this->owner_user_id,
            'default_sprint_length_days' => $this->default_sprint_length_days,
            'wip_limit_per_member' => $this->wip_limit_per_member,
            'status' => $this->status,
            'my_role' => $user ? $this->membershipFor($user)?->role : null,
            'member_count' => $this->memberships_count ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}