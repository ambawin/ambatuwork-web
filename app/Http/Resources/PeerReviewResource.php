<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeerReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'peer_review_cycle_id' => $this->peer_review_cycle_id,
            'reviewer' => $this->is_anonymous_to_reviewee
                ? $this->when($this->reviewer_user_id === $request->user()->id, new UserResource($this->whenLoaded('reviewer') ?? $this->reviewer))
                : new UserResource($this->whenLoaded('reviewer') ?? $this->reviewer),
            'reviewee' => new UserResource($this->whenLoaded('reviewee') ?? $this->reviewee),
            'collaboration_score' => $this->collaboration_score,
            'delivery_score' => $this->delivery_score,
            'communication_score' => $this->communication_score,
            'continue_feedback' => $this->continue_feedback,
            'improve_feedback' => $this->improve_feedback,
            'is_anonymous_to_reviewee' => $this->is_anonymous_to_reviewee,
            'submitted_at' => $this->submitted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
