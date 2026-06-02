<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetroItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'retrospective_id',
        'author_user_id',
        'type',
        'body',
        'assigned_to_user_id',
        'due_sprint_id',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
        ];
    }

    public function retrospective(): BelongsTo
    {
        return $this->belongsTo(Retrospective::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function dueSprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class, 'due_sprint_id');
    }
}
