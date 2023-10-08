<?php

namespace Tests\Feature;

use App\Entities\StreakState;
use App\UseCase\GetStreakWeeks;
use App\UseCase\GetUserMaxStreakCount;
use App\UseCase\UpdateHitsForWeek;
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
    $createUseCase = new UpdateHitsForWeek();
    foreach ($fixtures as $week) {
        $weekOfYear = WeekOfYear::fromString($week['week']);
        $createUseCase->execute($userUuid, $weekOfYear, $week['hits']);
    }
}

beforeEach(function () {
    $this->userA = Str::uuid()->toString();
    $this->userB = Str::uuid()->toString();

    // Seed the database with weekly HITs for users

    // User A has two streaks, one with a streak count of 4, and one with 2
    seedDatabase($this->userA, [
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
    seedDatabase($this->userB, [
        ['week' => '202252', 'hits' => 32], // Fulfilled
        ['week' => '202301', 'hits' => 40], // Fulfilled
        ['week' => '202301', 'hits' => 38], // Fulfilled
        // Gap of user activity until week 5
        ['week' => '202305', 'hits' => 32], // Fulfilled
        ['week' => '202306', 'hits' => 32], // Fulfilled
    ]);
});

describe('GetStreakWeeks', function () {
    test('returns the correct streak weeks for each user', function () {
        $sut = new GetStreakWeeks();

        $streakWeeks = $sut->execute($this->userA);
        expect($streakWeeks)
            ->toContainStreakWeek(week: '202302', hit: 16, state: StreakState::notFulfilled)
            ->toContainStreakWeek(week: '202303', hit: 40, state: StreakState::fulfilled)
            ->toContainStreakWeek(week: '202304', hit: 16, state: StreakState::repaired)
            ->toContainStreakWeek(week: '202305', hit: 32 + 16, state: StreakState::fulfilled)
            ->toContainStreakWeek(week: '202306', hit: 0, state: StreakState::notFulfilled)
            ->toContainStreakWeek(week: '202307', hit: 32, state: StreakState::fulfilled)
            ->toContainStreakWeek(week: '202308', hit: 40, state: StreakState::fulfilled);
    });

    test('returns correct streak weeks after merging two streaks', function () {
        $createUseCase = new UpdateHitsForWeek();

        // User retroactively logs a workouts for week 6, so it now achieves the goal
        seedDatabase($this->userA, [
            ['week' => '202306', 'hits' => 32]
        ]);
        $createUseCase->execute($this->userA, WeekOfYear::fromString('202306'), 32);

        $sut = new GetStreakWeeks();
        $streakWeeks = $sut->execute($this->userA);

        expect($streakWeeks)
            ->toContainStreakWeek(week: '202302', hit: 16, state: StreakState::notFulfilled)
            ->toContainStreakWeek(week: '202303', hit: 40, state: StreakState::fulfilled)
            ->toContainStreakWeek(week: '202304', hit: 16, state: StreakState::repaired)
            ->toContainStreakWeek(week: '202305', hit: 32 + 16, state: StreakState::fulfilled)
            ->toContainStreakWeek(week: '202306', hit: 32, state: StreakState::fulfilled)
            ->toContainStreakWeek(week: '202307', hit: 32, state: StreakState::fulfilled)
            ->toContainStreakWeek(week: '202308', hit: 40, state: StreakState::fulfilled);
    });

    test('returns correct streak weeks after repairing a broken week', function () {
        $createUseCase = new UpdateHitsForWeek();

        // User retroactively logs a workouts for week 7, so it now achieves the goal and repairs week 6
        $createUseCase->execute($this->userA, WeekOfYear::fromString('202307'), 64);


        $sut = new GetStreakWeeks();
        $streakWeeks = $sut->execute($this->userA);

        expect($streakWeeks)
            ->toContainStreakWeek(week: '202302', hit: 16, state: StreakState::notFulfilled)
            ->toContainStreakWeek(week: '202303', hit: 40, state: StreakState::fulfilled)
            ->toContainStreakWeek(week: '202304', hit: 16, state: StreakState::repaired)
            ->toContainStreakWeek(week: '202305', hit: 32 + 16, state: StreakState::fulfilled)
            ->toContainStreakWeek(week: '202306', hit: 0, state: StreakState::repaired)
            ->toContainStreakWeek(week: '202307', hit: 64, state: StreakState::fulfilled)
            ->toContainStreakWeek(week: '202308', hit: 40, state: StreakState::fulfilled);
    });

    test('returns correct streak weeks after splitting two streaks', function () {
        $createUseCase = new UpdateHitsForWeek();

        // User retroactively logs a workouts for week 6, so it now achieves the goal
        $createUseCase->execute($this->userA, WeekOfYear::fromString('202306'), 32);
        // User deletes the workout and now fails the goal
        $createUseCase->execute($this->userA, WeekOfYear::fromString('202306'), 0);

        $sut = new GetStreakWeeks();
        $streakWeeks = $sut->execute($this->userA);

        expect($streakWeeks)
            ->toContainStreakWeek(week: '202302', hit: 16, state: StreakState::notFulfilled)
            ->toContainStreakWeek(week: '202303', hit: 40, state: StreakState::fulfilled)
            ->toContainStreakWeek(week: '202304', hit: 16, state: StreakState::repaired)
            ->toContainStreakWeek(week: '202305', hit: 32 + 16, state: StreakState::fulfilled)
            ->toContainStreakWeek(week: '202306', hit: 32, state: StreakState::fulfilled)
            ->toContainStreakWeek(week: '202307', hit: 32, state: StreakState::fulfilled)
            ->toContainStreakWeek(week: '202308', hit: 40, state: StreakState::fulfilled);
    });
});

describe('GetUserMaxStreakCount', function () {
    test('returns the correct streak progress for each user', function () {
        $sut = new GetUserMaxStreakCount();

        expect($sut->execute($this->userA))->toEqual(4);
        expect($sut->execute($this->userB))->toEqual(3);
    });
});
