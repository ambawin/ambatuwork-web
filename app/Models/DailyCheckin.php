<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyCheckin extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'sprint_id',
        'user_id',
        'checkin_date',
        'yesterday',
        'today',
        'blockers',
        'confidence_score',
    ];

    protected function casts(): array
    {
        return [
            'checkin_date' => 'date',
            'confidence_score' => 'integer',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
