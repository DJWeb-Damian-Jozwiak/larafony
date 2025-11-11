<?php

declare(strict_types=1);

namespace Larafony\Framework\Events\Cache;

final readonly class CacheHit
{
    public function __construct(
        public string $key,
        public mixed $value,
    ) {
    }
}
