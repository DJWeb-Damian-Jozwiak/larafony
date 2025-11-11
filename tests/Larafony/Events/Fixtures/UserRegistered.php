<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Events\Fixtures;

final readonly class UserRegistered
{
    public function __construct(
        public string $email,
        public string $name,
    ) {
    }
}
