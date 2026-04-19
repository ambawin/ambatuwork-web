<?php

namespace App\Models;

use App\Enums\SprintStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sprint extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'title',
        'description',
        'start_date',
        'deadline_at',
        'status',
        'base_points',
        'late_penalty_points',
        'rejection_penalty_points',
        'peer_review_bonus_max',
        'created_by',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'deadline_at' => 'datetime',
            'closed_at' => 'datetime',
            'status' => SprintStatus::class,
            'base_points' => 'integer',
            'late_penalty_points' => 'integer',
            'rejection_penalty_points' => 'integer',
            'peer_review_bonus_max' => 'integer',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function peerReviews(): HasMany
    {
        return $this->hasMany(PeerReview::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }
}