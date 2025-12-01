<?php

declare(strict_types=1);

namespace Larafony\Framework\Events;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

final class ListenerProvider implements ListenerProviderInterface
{
    /**
     * @var array<class-string, array<int, array<int, callable|array{class-string|object, string}>>>
     */
    private array $listeners = [];

    public function __construct(
        private readonly ?ContainerInterface $container = null,
    ) {
    }

    /**
     * @param class-string $eventClass
     * @param callable|array{class-string|object, string} $listener
     * @param int $priority
     */
    public function listen(string $eventClass, callable|array $listener, int $priority = 0): void
    {
        if (! isset($this->listeners[$eventClass])) {
            $this->listeners[$eventClass] = [];
        }

        if (! isset($this->listeners[$eventClass][$priority])) {
            $this->listeners[$eventClass][$priority] = [];
        }

        $this->listeners[$eventClass][$priority][] = $listener;
    }

    /**
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        $eventClass = $event::class;

        if (! isset($this->listeners[$eventClass])) {
            return [];
        }

        // Sort by priority (descending)
        krsort($this->listeners[$eventClass]);

        $listeners = [];
        foreach ($this->listeners[$eventClass] as $priorityListeners) {
            foreach ($priorityListeners as $listener) {
                $listeners[] = $this->resolveListener($listener);
            }
        }

        return $listeners;
    }

    /**
     * @param callable|array{class-string|object, string} $listener
     */
    private function resolveListener(callable|array $listener): callable
    {
        // Arrays are always callable in this context (class, method)
        if (is_array($listener)) {
            [$classOrInstance, $method] = $listener;

            // If it's already an instance, just return it
            if (is_object($classOrInstance)) {
                return [$classOrInstance, $method];
            }

            // Otherwise, resolve from container or create new instance
            if ($this->container !== null && $this->container->has($classOrInstance)) {
                $instance = $this->container->get($classOrInstance);
            } else {
                $instance = new $classOrInstance();
            }

            return [$instance, $method];
        }

        return $listener;
    }
}
