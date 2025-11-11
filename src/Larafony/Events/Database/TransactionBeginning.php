<?php

declare(strict_types=1);

namespace Larafony\Framework\Events\Database;

final readonly class TransactionBeginning
{
    public function __construct(
        public string $connection = 'default',
    ) {
    }
}
