<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DefinitionOfDone extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'checklist',
        'is_active',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'checklist' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public static function defaultTitle(): string
    {
        return 'Default Definition of Done';
    }

    public static function defaultChecklist(): array
    {
        return [
            'Acceptance criteria are completed',
            'Reviewed by another team member',
            'No known critical bugs',
            'Demoable in Sprint Review',
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

    public function backlogItems(): HasMany
    {
        return $this->hasMany(BacklogItem::class, 'project_id', 'project_id');
    }
}