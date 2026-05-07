<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SprintItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sprint_id',
        'backlog_item_id',
        'committed_points',
        'added_by_user_id',
        'added_at',
    ];

    protected function casts(): array
    {
        return [
            'committed_points' => 'integer',
            'added_at' => 'datetime',
        ];
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function backlogItem(): BelongsTo
    {
        return $this->belongsTo(BacklogItem::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }
}