<?php

namespace App\UseCases;

use App\Entities\WeekOfYear;

class UpdateHitsForWeek
{
    public function __construct()
    {
    }

    public function execute(string $userUuid, WeekOfYear $weekOfYear, int $hitsEarned): void
    {
        // TODO: Implement this
    }
}
