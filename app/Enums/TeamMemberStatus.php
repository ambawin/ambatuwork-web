<?php

namespace App\Enums;

enum TeamMemberStatus: string
{
    case Active = 'active';
    case Left = 'left';
    case Removed = 'removed';
}