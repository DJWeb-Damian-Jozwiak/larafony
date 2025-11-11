<?php

declare(strict_types=1);

namespace Larafony\Framework\Events;

use Psr\EventDispatcher\StoppableEventInterface;

abstract class StoppableEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
