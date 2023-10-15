<?php

namespace Tests\Feature;

use App\Entities\StreakNodeState;
use App\UseCases\GetCurrentStreakProgress;
use App\UseCases\GetStreakWeeks;
use App\UseCases\GetUserMaxStreakCount;
use App\UseCases\UpdateHitsForWeek;
use App\Entities\WeekOfYear;
use Illuminate\Support\Str;

/**
 * Helper method to seed the correct HITs for a collection of weeks.
 *
 * @param string $userUuid
 * @param array<int, array{week: int, hits: int}> $fixtures
 * @return void
 */
function seedDatabase(string $userUuid, array $fixtures): void {
    $createUseCase = resolve(UpdateHitsForWeek::class);
    foreach ($fixtures as $week) {
        $weekOfYear = WeekOfYear::fromString($week['week']);
        $createUseCase->execute($userUuid, $weekOfYear, $week['hits']);
    }
}

function seedDefaultHitStreakData($test): void {
    $test->userA = Str::uuid()->toString();
    $test->userB = Str::uuid()->toString();

    // Seed the database with weekly HITs for users

    // User A has two streaks, one with a streak count of 4, and one with 2
    seedDatabase($test->userA, [
        // Did nothing in week 1
        ['week' => '202302', 'hits' => 16], // No streak
        ['week' => '202303', 'hits' => 40], // Fulfilled
        ['week' => '202304', 'hits' => 16], // missed goal
        ['week' => '202305', 'hits' => 32 + 16], // repaired week 4
        // Did nothing in week 6, no streak
        ['week' => '202307', 'hits' => 32], // Start of new streak
        ['week' => '202308', 'hits' => 40], // Fulfilled
    ]);

    // User A has two streaks, one with a streak count of 3, and one with 2
    seedDatabase($test->userB, [
        ['week' => '202252', 'hits' => 32], // Fulfilled
        ['week' => '202301', 'hits' => 40], // Fulfilled
        ['week' => '202301', 'hits' => 38], // Fulfilled
        // Gap of user activity until week 5
        ['week' => '202305', 'hits' => 32], // Fulfilled
        ['week' => '202306', 'hits' => 32], // Fulfilled
    ]);
}

describe('GetStreakWeeks', function () {
    beforeEach(function () {
        seedDefaultHitStreakData($this);
    });

    test('returns the correct streak weeks for each user', function () {
        $sut = resolve(GetStreakWeeks::class);

        $streakWeeks = $sut->execute($this->userA);
        expect($streakWeeks)
            ->toContainStreakWeek(week: '202302', hit: 16, state: StreakNodeState::notFulfilled)
            ->toContainStreakWeek(week: '202303', hit: 40, state: StreakNodeState::fulfilled)
            ->toContainStreakWeek(week: '202304', hit: 16, state: StreakNodeState::repaired)
            ->toContainStreakWeek(week: '202305', hit: 32 + 16, state: StreakNodeState::fulfilled)
            ->toContainStreakWeek(week: '202306', hit: 0, state: StreakNodeState::notFulfilled)
            ->toContainStreakWeek(week: '202307', hit: 32, state: StreakNodeState::fulfilled)
            ->toContainStreakWeek(week: '202308', hit: 40, state: StreakNodeState::fulfilled);
    });

    test('returns correct streak weeks after merging two streaks', function () {
        $createUseCase = resolve(UpdateHitsForWeek::class);

        // User retroactively logs a workouts for week 6, so it now achieves the goal
        seedDatabase($this->userA, [
            ['week' => '202306', 'hits' => 32]
        ]);
        $createUseCase->execute($this->userA, WeekOfYear::fromString('202306'), 32);

        $sut = resolve(GetStreakWeeks::class);
        $streakWeeks = $sut->execute($this->userA);

        expect($streakWeeks)
            ->toContainStreakWeek(week: '202302', hit: 16, state: StreakNodeState::notFulfilled)
            ->toContainStreakWeek(week: '202303', hit: 40, state: StreakNodeState::fulfilled)
            ->toContainStreakWeek(week: '202304', hit: 16, state: StreakNodeState::repaired)
            ->toContainStreakWeek(week: '202305', hit: 32 + 16, state: StreakNodeState::fulfilled)
            ->toContainStreakWeek(week: '202306', hit: 32, state: StreakNodeState::fulfilled)
            ->toContainStreakWeek(week: '202307', hit: 32, state: StreakNodeState::fulfilled)
            ->toContainStreakWeek(week: '202308', hit: 40, state: StreakNodeState::fulfilled);
    });

    test('returns correct streak weeks after repairing a broken week', function () {
        $createUseCase = resolve(UpdateHitsForWeek::class);

        // User retroactively logs a workouts for week 7, so it now achieves the goal and repairs week 6
        $createUseCase->execute($this->userA, WeekOfYear::fromString('202307'), 64);

        $sut = resolve(GetStreakWeeks::class);
        $streakWeeks = $sut->execute($this->userA);

        expect($streakWeeks)
            ->toContainStreakWeek(week: '202302', hit: 16, state: StreakNodeState::notFulfilled)
            ->toContainStreakWeek(week: '202303', hit: 40, state: StreakNodeState::fulfilled)
            ->toContainStreakWeek(week: '202304', hit: 16, state: StreakNodeState::repaired)
            ->toContainStreakWeek(week: '202305', hit: 32 + 16, state: StreakNodeState::fulfilled)
            ->toContainStreakWeek(week: '202306', hit: 0, state: StreakNodeState::repaired)
            ->toContainStreakWeek(week: '202307', hit: 64, state: StreakNodeState::fulfilled)
            ->toContainStreakWeek(week: '202308', hit: 40, state: StreakNodeState::fulfilled);
    });

    test('returns correct streak weeks after splitting two streaks', function () {
        $createUseCase = resolve(UpdateHitsForWeek::class);

        // User retroactively logs a workouts for week 6, so it now achieves the goal
        $createUseCase->execute($this->userA, WeekOfYear::fromString('202306'), 32);
        // User deletes the workout and now fails the goal
        $createUseCase->execute($this->userA, WeekOfYear::fromString('202306'), 0);

        $sut = resolve(GetStreakWeeks::class);
        $streakWeeks = $sut->execute($this->userA);

        expect($streakWeeks)
            ->toContainStreakWeek(week: '202302', hit: 16, state: StreakNodeState::notFulfilled)
            ->toContainStreakWeek(week: '202303', hit: 40, state: StreakNodeState::fulfilled)
            ->toContainStreakWeek(week: '202304', hit: 16, state: StreakNodeState::repaired)
            ->toContainStreakWeek(week: '202305', hit: 32 + 16, state: StreakNodeState::fulfilled)
            ->toContainStreakWeek(week: '202306', hit: 0, state: StreakNodeState::notFulfilled)
            ->toContainStreakWeek(week: '202307', hit: 32, state: StreakNodeState::fulfilled)
            ->toContainStreakWeek(week: '202308', hit: 40, state: StreakNodeState::fulfilled);
    });
});

describe('GetCurrentStreakProgress', function () {
    test('Returns streak of 1 when user reached HIT goal in previous week', function () {
        $previousWeek = WeekOfYear::currentWeek()->subWeeks(1);
        $userUuid = Str::uuid()->toString();
        seedDatabase($userUuid, [
            ['week' => (string) $previousWeek, 'hits' => 32], // Fulfilled
        ]);

        /** @var GetCurrentStreakProgress $sut */
        $sut = resolve(GetCurrentStreakProgress::class);
        $progress = $sut->execute($userUuid);

        expect($progress)
            ->streakCount->toEqual(1)
            ->hitMinutesPendingThisWeek->toEqual(32)
            ->hitMinutesPendingToRepair->toEqual(0)
            ->failedToRepairInPreviousWeek->toEqual(false);
    });
    test('Returns streak of 1 when user reached HIT streak this week', function () {
        $thisWeek = WeekOfYear::currentWeek();
        $userUuid = Str::uuid()->toString();
        seedDatabase($userUuid, [
            ['week' => (string) $thisWeek, 'hits' => 32], // Fulfilled
        ]);

        /** @var GetCurrentStreakProgress $sut */
        $sut = resolve(GetCurrentStreakProgress::class);
        $progress = $sut->execute($userUuid);

        expect($progress)
            ->streakCount->toEqual(1)
            ->hitMinutesPendingThisWeek->toEqual(0, 'Should be 0 since HIT goal is achieved this week')
            ->hitMinutesPendingToRepair->toEqual(0, 'Should be nothing to repair')
            ->failedToRepairInPreviousWeek->toEqual(false);
    });
    test('Returns correct hitMinutesPendingThisWeek when partially meeting the HIT goal this week', function () {
        $thisWeek = WeekOfYear::currentWeek();
        $userUuid = Str::uuid()->toString();
        seedDatabase($userUuid, [
            ['week' => (string) $thisWeek, 'hits' => 10], // Not fulfilled
        ]);

        /** @var GetCurrentStreakProgress $sut */
        $sut = resolve(GetCurrentStreakProgress::class);
        $progress = $sut->execute($userUuid);

        expect($progress)
            ->streakCount->toEqual(0)
            ->hitMinutesPendingThisWeek->toEqual(32 - 10, 'Should be 22 since only 10 HIT is achieved')
            ->hitMinutesPendingToRepair->toEqual(0, 'Should be nothing to repair')
            ->failedToRepairInPreviousWeek->toEqual(false);
    });
    test('Does not return hitMinutesPendingToRepair when last week was not fulfilled but not part of an existing streak', function () {
        $previousWeek = WeekOfYear::currentWeek()->subWeeks(1);
        $thisWeek = WeekOfYear::currentWeek();
        $userUuid = Str::uuid()->toString();
        seedDatabase($userUuid, [
            ['week' => (string) $previousWeek, 'hits' => 20], // Not fulfilled, no streak started
            ['week' => (string) $thisWeek, 'hits' => 30], // Not fulfilled
        ]);

        /** @var GetCurrentStreakProgress $sut */
        $sut = resolve(GetCurrentStreakProgress::class);
        $progress = $sut->execute($userUuid);

        expect($progress)
            ->streakCount->toEqual(0)
            ->hitMinutesPendingThisWeek->toEqual(2, 'Should be 22 since only 10 HIT is achieved')
            ->hitMinutesPendingToRepair->toEqual(0, 'User hasn\'t started a streak yet, so nothing to repair')
            ->failedToRepairInPreviousWeek->toEqual(false);
    });
    test('Returns correct state when the HIT goal last week was partially met', function () {
        $twoWeeksAgo = WeekOfYear::currentWeek()->subWeeks(2);
        $previousWeek = WeekOfYear::currentWeek()->subWeeks(1);
        $thisWeek = WeekOfYear::currentWeek();
        $userUuid = Str::uuid()->toString();
        seedDatabase($userUuid, [
            ['week' => (string) $twoWeeksAgo, 'hits' => 32], // Fulfilled
            ['week' => (string) $previousWeek, 'hits' => 20], // Not fulfilled
            ['week' => (string) $thisWeek, 'hits' => 30], // Not fulfilled
        ]);

        /** @var GetCurrentStreakProgress $sut */
        $sut = resolve(GetCurrentStreakProgress::class);
        $progress = $sut->execute($userUuid);

        expect($progress)
            ->streakCount->toEqual(1)
            ->hitMinutesPendingThisWeek->toEqual(2, 'Should be 22 since only 10 HIT is achieved')
            ->hitMinutesPendingToRepair->toEqual(12 + 2, 'Needs to repair 12 from last week in addition to 2 missing this week')
            ->failedToRepairInPreviousWeek->toEqual(false);
    });
    test('Returns streak of 2 when the HIT goal two weeks ago and last week was fulfilled', function () {
        $twoWeeksAgo = WeekOfYear::currentWeek()->subWeeks(2);
        $previousWeek = WeekOfYear::currentWeek()->subWeeks(1);
        $userUuid = Str::uuid()->toString();
        seedDatabase($userUuid, [
            ['week' => (string) $twoWeeksAgo, 'hits' => 32], // Fulfilled
            ['week' => (string) $previousWeek, 'hits' => 32], // Fulfilled
        ]);

        /** @var GetCurrentStreakProgress $sut */
        $sut = resolve(GetCurrentStreakProgress::class);
        $progress = $sut->execute($userUuid);

        expect($progress)
            ->streakCount->toEqual(2)
            ->hitMinutesPendingThisWeek->toEqual(32, 'Should be 32 since no HITs achieved this week')
            ->hitMinutesPendingToRepair->toEqual(0, 'Nothing to repair')
            ->failedToRepairInPreviousWeek->toEqual(false, 'Nothing was broken');
    });
    test('Returns streak of 2 when meeting the HIT goal last week and this week', function () {
        $previousWeek = WeekOfYear::currentWeek()->subWeeks(1);
        $thisWeek = WeekOfYear::currentWeek();
        $userUuid = Str::uuid()->toString();
        seedDatabase($userUuid, [
            ['week' => (string) $previousWeek, 'hits' => 32], // Fulfilled
            ['week' => (string) $thisWeek, 'hits' => 32], // Fulfilled
        ]);

        /** @var GetCurrentStreakProgress $sut */
        $sut = resolve(GetCurrentStreakProgress::class);
        $progress = $sut->execute($userUuid);

        expect($progress)
            ->streakCount->toEqual(2)
            ->hitMinutesPendingThisWeek->toEqual(0, 'Should be no HITs left to meet this week\'s goal')
            ->hitMinutesPendingToRepair->toEqual(0, 'Nothing to repair')
            ->failedToRepairInPreviousWeek->toEqual(false, 'Nothing was broken');
    });
    test('Returns streak of 3 when the previous week is fully repaired', function () {
        $twoWeeksAgo = WeekOfYear::currentWeek()->subWeeks(2);
        $previousWeek = WeekOfYear::currentWeek()->subWeeks(1);
        $thisWeek = WeekOfYear::currentWeek();
        $userUuid = Str::uuid()->toString();
        seedDatabase($userUuid, [
            ['week' => (string) $twoWeeksAgo, 'hits' => 32], // Fulfilled
            ['week' => (string) $previousWeek, 'hits' => 22], // Broken (repairable, missing 10 HIT)
            ['week' => (string) $thisWeek, 'hits' => 32 + 10], // Fulfilled + repairing 10 missing from last week
        ]);

        /** @var GetCurrentStreakProgress $sut */
        $sut = resolve(GetCurrentStreakProgress::class);
        $progress = $sut->execute($userUuid);

        expect($progress)
            ->streakCount->toEqual(3)
            ->hitMinutesPendingThisWeek->toEqual(0, 'Should be no HITs left to meet this week\'s goal')
            ->hitMinutesPendingToRepair->toEqual(0, 'Nothing to repair')
            ->failedToRepairInPreviousWeek->toEqual(false, 'Nothing was broken');
    });
});

describe('GetUserMaxStreakCount', function () {
    beforeEach(function () {
        seedDefaultHitStreakData($this);
    });
    test('returns the max streak count for each user', function () {
        $sut = new GetUserMaxStreakCount();

        expect($sut->execute($this->userA))->toEqual(4);
        expect($sut->execute($this->userB))->toEqual(3);
    });
});
