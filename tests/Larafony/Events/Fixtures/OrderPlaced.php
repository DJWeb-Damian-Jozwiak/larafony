<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Events\Fixtures;

use Larafony\Framework\Events\StoppableEvent;

final class OrderPlaced extends StoppableEvent
{
    public function __construct(
        public readonly int $orderId,
        public readonly float $total,
    ) {
    }
}
