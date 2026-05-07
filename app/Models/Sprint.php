<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sprint extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'sprint_goal',
        'status',
        'start_date',
        'end_date',
        'created_by_user_id',
        'closed_by_user_id',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(BacklogItem::class, 'sprint_items')
            ->withPivot(['committed_points', 'added_by_user_id', 'added_at'])
            ->withTimestamps();
    }

    public function sprintItems(): HasMany
    {
        return $this->hasMany(SprintItem::class);
    }
}