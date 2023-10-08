<?php
declare(strict_types=1);

use App\Entities\WeekOfYear;
use Carbon\CarbonImmutable;

test('__construct() builds a WeekOfYear with the given year and week', function () {
    $week = new WeekOfYear(2023, 1);
    expect($week)
        ->year->toEqual(2023)
        ->weekNumber->toEqual(1);
});

test('equals() returns true if the two weeks have the same year and week number', function () {
    $week = new WeekOfYear(2023, 1);
    $otherWeek = new WeekOfYear(2023, 1);
    expect($week->equals($otherWeek))->toBeTrue();
});

test('equals() returns false if the two weeks do not have the same year and week number', function () {
    $week = new WeekOfYear(2023, 1);
    $otherWeek = new WeekOfYear(2022, 1);
    expect($week->equals($otherWeek))->toBeFalse();
});

test('fromDate() builds a WeekOfYear that represents the week of the given date', function ($date, $expectedYear, $expectedWeekNumber) {
    $week = WeekOfYear::fromDate(CarbonImmutable::parse($date));

    expect($week)
        ->year->toEqual($expectedYear)
        ->weekNumber->toEqual($expectedWeekNumber);
})->with([
    ['1977-01-01', 1976, 53],
    ['1977-01-02', 1976, 53],
    ['1977-12-31', 1977, 52],
    ['1978-01-01', 1977, 52],
    ['1978-01-02', 1978, 1],
    ['1978-12-31', 1978, 52],
    ['1979-01-01', 1979, 1],
    ['1979-12-30', 1979, 52],
    ['1979-12-31', 1980, 1],
    ['1980-01-01', 1980, 1],
    ['1980-12-28', 1980, 52],
    ['1980-12-29', 1981, 1],
    ['1980-12-30', 1981, 1],
    ['1980-12-31', 1981, 1],
    ['1981-01-01', 1981, 1],
    ['1981-12-31', 1981, 53],
    ['1982-01-01', 1981, 53],
    ['1982-01-02', 1981, 53],
    ['1982-01-03', 1981, 53],
    ['2018-12-31', 2019, 1],
    ['2023-01-01', 2022, 52]
]);

test('fromString() builds a WeekOfYear from the given string', function () {
    $week = WeekOfYear::fromString('202252');
    $expectedWeek = new WeekOfYear(2022, 52);
    expect($week->equals($expectedWeek))->toBeTrue();
});

test('__toString() returns a string representation of the WeekOfYear', function () {
    $week = new WeekOfYear(2023, 1);
    expect((string) $week)->toEqual('202301');
});

test('currentWeek() builds an instance based on the current week', function () {
    $week = WeekOfYear::currentWeek();
    $expectedWeek = WeekOfYear::fromDate(CarbonImmutable::now()->startOfWeek());

    expect($week->equals($expectedWeek))->toBeTrue();
});

test('addWeeks() adds the given number of weeks', function () {
    $week = (new WeekOfYear(2022, 52))->addWeeks(1);
    $expectedWeek = new WeekOfYear(2023, 1);
    expect($week->equals($expectedWeek))->toBeTrue();
});

test('subWeeks() subtracts the given number of weeks', function () {
    $week = (new WeekOfYear(2023, 1))->subWeeks(1);
    $expectedWeek = new WeekOfYear(2022, 52);
    expect($week->equals($expectedWeek))->toBeTrue();
});
