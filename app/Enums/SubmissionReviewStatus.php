<?php

namespace App\Enums;

enum SubmissionReviewStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}