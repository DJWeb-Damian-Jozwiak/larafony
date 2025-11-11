<?php

declare(strict_types=1);

namespace Larafony\Framework\Events\Cache;

final readonly class CacheMissed
{
    public function __construct(
        public string $key,
    ) {
    }
}
