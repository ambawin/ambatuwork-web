<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'expires_at' => $this->expires_at,
            'accepted_at' => $this->accepted_at,
            'created_at' => $this->created_at,
        ];
    }
}