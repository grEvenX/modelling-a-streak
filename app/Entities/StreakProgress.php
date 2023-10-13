<?php

namespace App\Entities;

class StreakProgress
{
    public function __construct(
        public readonly int $streakCount,
        public readonly int $hitMinutesPendingThisWeek,
        public readonly int $hitMinutesPendingToRepair,
        public readonly bool $failedToRepairInPreviousWeek
    ) {

    }
}
