<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets;

use Larafony\Framework\WebSockets\Contracts\ConnectionContract;

final class EventDispatcher
{
    /** @var array<string, array<int, callable>> */
    private array $listeners = [];

    public function addListener(string $event, callable $callback): void
    {
        $this->listeners[$event][] = $callback;
    }

    public function removeListener(string $event, callable $callback): void
    {
        if (! isset($this->listeners[$event])) {
            return;
        }

        $this->listeners[$event] = array_values(array_filter(
            $this->listeners[$event],
            static fn (callable $listener): bool => $listener !== $callback
        ));
    }

    public function dispatch(string $event, mixed $data, ConnectionContract $connection): void
    {
        if (! isset($this->listeners[$event])) {
            return;
        }

        foreach ($this->listeners[$event] as $listener) {
            $listener($data, $connection, $this);
        }
    }

    public function hasListeners(string $event): bool
    {
        return isset($this->listeners[$event]) && $this->listeners[$event] !== [];
    }
}
