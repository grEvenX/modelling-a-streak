<?php

namespace App\Entities;

enum StreakNodeState: string
{
    case fulfilled = 'fulfilled';
    case repaired = 'repaired';
    case notFulfilled = 'not_fulfilled';
}
