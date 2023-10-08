<?php

namespace App\Entities;

class StreakProgress
{
    public function __construct(
        public readonly int $streakCount,
        public readonly int $hitRemainingThisWeek,
        public readonly int $hitRequiredToRepair,
        public readonly bool $previousWeekFailedToRepair
    ) {

    }
}
