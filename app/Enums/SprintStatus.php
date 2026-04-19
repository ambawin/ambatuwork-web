<?php

namespace App\Enums;

enum SprintStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Review = 'review';
    case Closed = 'closed';
}