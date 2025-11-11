<?php

declare(strict_types=1);

namespace Larafony\Framework\Events\Framework;

final readonly class ApplicationBooted
{
    public function __construct(
        public float $bootTime,
    ) {
    }
}
