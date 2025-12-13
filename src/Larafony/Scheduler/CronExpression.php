<?php

declare(strict_types=1);

namespace Larafony\Framework\Scheduler;

use Larafony\Framework\Clock\ClockFactory;

class CronExpression
{
    /**
     * @var array<int, string>
     */
    private array $parts;

    public function __construct(string $expression)
    {
        $this->parts = explode(' ', $expression);
        if (count($this->parts) !== 5) {
            throw new \InvalidArgumentException('Invalid cron expression');
        }
    }

    public function isDue(): bool
    {
        $now = ClockFactory::now();

        return $this->matchesPart($now->format('i'), $this->parts[0]) &&  // minute
            $this->matchesPart($now->format('H'), $this->parts[1]) &&  // hour
            $this->matchesPart($now->format('d'), $this->parts[2]) &&  // day
            $this->matchesPart($now->format('m'), $this->parts[3]) &&  // month
            $this->matchesPart($now->format('w'), $this->parts[4]);    // day of week
    }

    private function matchesPart(string $value, string $pattern): bool
    {
        if ($pattern === '*') {
            return true;
        }

        $parts = explode(',', $pattern);

        return $this->matchesAnyPart($value, $parts);
    }

    /**
     * @param array<int, string> $parts
     */
    private function matchesAnyPart(string $value, array $parts): bool
    {
        return array_any($parts, fn ($part) => $this->matchesSinglePart($value, $part));
    }

    private function matchesSinglePart(string $value, string $part): bool
    {
        if (str_contains($part, '/')) {
            return $this->matchesFrequency($value, $part);
        }

        if (str_contains($part, '-')) {
            return $this->matchesRange($value, $part);
        }

        return (int) $value === (int) $part;
    }

    private function matchesFrequency(string $value, string $part): bool
    {
        [$num, $freq] = explode('/', $part);
        return $num === '*' && (int) $value % (int) $freq === 0;
    }

    private function matchesRange(string $value, string $part): bool
    {
        [$start, $end] = explode('-', $part);
        return (int) $value >= (int) $start && (int) $value <= (int) $end;
    }
}
