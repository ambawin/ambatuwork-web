<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeerReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'peer_review_cycle_id',
        'reviewer_user_id',
        'reviewee_user_id',
        'collaboration_score',
        'delivery_score',
        'communication_score',
        'continue_feedback',
        'improve_feedback',
        'is_anonymous_to_reviewee',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'collaboration_score' => 'integer',
            'delivery_score' => 'integer',
            'communication_score' => 'integer',
            'is_anonymous_to_reviewee' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function peerReviewCycle(): BelongsTo
    {
        return $this->belongsTo(PeerReviewCycle::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_user_id');
    }
}
