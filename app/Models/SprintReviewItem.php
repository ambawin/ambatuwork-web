<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SprintReviewItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sprint_review_id',
        'backlog_item_id',
        'decision',
        'notes',
        'decided_by_user_id',
    ];

    public function sprintReview(): BelongsTo
    {
        return $this->belongsTo(SprintReview::class);
    }

    public function backlogItem(): BelongsTo
    {
        return $this->belongsTo(BacklogItem::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }
}
