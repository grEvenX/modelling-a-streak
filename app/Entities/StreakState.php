<?php

namespace App\Entities;

enum StreakState: string
{
    case fulfilled = 'fulfilled';
    case repaired = 'repaired';
    case notFulfilled = 'not_fulfilled';
}
