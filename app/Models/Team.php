<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    /**
     * Relationships
     */

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->hasMany(TeamMember::class);
    }

    public function invitations()
    {
        return $this->hasMany(TeamInvitation::class);
    }

    public function sprints()
    {
        return $this->hasMany(Sprint::class);
    }

    public function activities()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
