<?php

namespace App\UseCases;

use App\Entities\StreakProgress;

class GetCurrentStreakProgress
{
    public function execute(string $userUuid): StreakProgress
    {
        // Extra Challenge; Can you implement this without traversing all streaks for the user?
        // TODO: Needs implementation
        return new StreakProgress(
            streakCount: 0,
            hitMinutesPendingThisWeek: 0,
            hitMinutesPendingToRepair: 0,
            failedToRepairInPreviousWeek: false
        );
    }
}
