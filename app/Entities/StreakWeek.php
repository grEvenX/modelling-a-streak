<?php

namespace App\Entities;

class StreakWeek
{
    public function __construct(
        public string $streakIdentifier,
        public readonly WeekOfYear $weekOfYear,
        public readonly int $hit,
        public readonly int $hitGoal,
        public readonly StreakState $state
    ) {
    }
}
