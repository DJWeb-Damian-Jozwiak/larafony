<?php

declare(strict_types=1);

namespace Larafony\Framework\Events\Cache;

final readonly class KeyWritten
{
    public function __construct(
        public string $key,
        public mixed $value,
        public ?int $ttl = null,
        public ?int $size = null,  // Size in bytes
    ) {
    }
}
