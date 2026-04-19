<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeerReview extends Model
{
    /**
     * Relationships
     */

    public function sprint()
    {
        return $this->belongsTo(Sprint::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function reviewee()
    {
        return $this->belongsTo(User::class, 'reviewee_user_id');
    }
}
