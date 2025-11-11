<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Events\Fixtures;

use Larafony\Framework\Events\Attributes\Listen;

final class InvalidListener
{
    #[Listen]
    public function onEventWithoutParameter(): void
    {
        // No parameter - cannot infer event class
    }
}
