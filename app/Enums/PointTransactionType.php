<?php

namespace App\Enums;

enum PointTransactionType: string
{
    case TaskApproved = 'task_approved';
    case TaskRejected = 'task_rejected';
    case TaskMissed = 'task_missed';
    case PeerReviewBonus = 'peer_review_bonus';
    case PeerReviewPenalty = 'peer_review_penalty';
    case ManualAdjustment = 'manual_adjustment';
}