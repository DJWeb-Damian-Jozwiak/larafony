<?php

declare(strict_types=1);

namespace Larafony\Framework\Log;

use DateTimeImmutable;
use Larafony\Framework\Clock\ClockFactory;

final readonly class Metadata
{
    public function __construct(
        private DateTimeImmutable $timestamp,
    ) {
    }

    public static function create(): self
    {
        return new self(
            timestamp: ClockFactory::now(),
        );
    }

    /**
     * @return array<string|int, mixed>
     */
    public function toArray(): array
    {
        return [
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s'),
        ];
    }
}
