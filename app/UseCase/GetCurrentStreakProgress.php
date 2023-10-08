<?php

namespace App\UseCase;

use App\Entities\StreakProgress;

class GetCurrentStreakProgress
{
    public function execute(string $userUuid): StreakProgress
    {
        // Extra Challenge; Can you implement this without traversing all streaks for the user?
        // TODO: Needs implementation
        return new StreakProgress(
            streakCount: 0,
            hitRemainingThisWeek: 0,
            hitRequiredToRepair: 0,
            previousWeekFailedToRepair: false
        );
    }
}
