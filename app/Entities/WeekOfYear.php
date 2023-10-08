<?php
declare(strict_types=1);

namespace App\Entities;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class WeekOfYear
{
    public function __construct(
        public readonly int $year,
        public readonly int $weekNumber,
    ) {
    }

    public static function currentWeek(): self
    {
        return static::fromDate(CarbonImmutable::now());
    }

    public static function fromDate(CarbonImmutable $date): self
    {
        // ISO 8601 week-numbering year. This has the same value as Y,
        // except that if the ISO week number (W) belongs to the previous or next year, that year is used instead.
        $year = (int) $date->format('o');
        $weekOfYear = (int) $date->format('W');

        return new self($year, $weekOfYear);
    }

    /**
     * @param string $string A string in the format YYYYWW
     */
    public static function fromString(string $string): self
    {
        $year = Str::substr($string, 0, 4);
        $week = Str::substr($string, 4, 2);

        return new self((int) $year, (int) $week);
    }

    public function addWeeks(int $weeks): self
    {
        $date = CarbonImmutable::now()
            ->setISODate($this->year, $this->weekNumber)
            ->addWeeks($weeks);

        return self::fromDate($date);
    }

    public function subWeeks(int $weeks): self
    {
        return $this->addWeeks(-$weeks);
    }

    public function equals(WeekOfYear $weekOfYear): bool
    {
        return $this->year === $weekOfYear->year
            && $this->weekNumber === $weekOfYear->weekNumber;
    }

    public function __toString(): string
    {
        $week = Str::of($this->weekNumber)->padLeft(2, '0');
        return "$this->year$week";
    }
}
