<?php

namespace App\UseCases;

use App\Entities\StreakWeek;
use Illuminate\Support\Collection;

/**
 * Use Case returning all Streak Weeks for a user
 */
class GetStreakWeeks
{
    /**
     * @param string $userUuid
     * @return Collection<int, StreakWeek>
     */
    public function execute(string $userUuid): Collection
    {
        // TODO: Needs to be implemented
        return Collection::make();
    }
}
