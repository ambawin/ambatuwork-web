<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Retrospective extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'sprint_id',
        'team_happiness_score',
    ];

    protected function casts(): array
    {
        return [
            'team_happiness_score' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RetroItem::class);
    }
}
