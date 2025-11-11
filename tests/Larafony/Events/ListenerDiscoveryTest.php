<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Events;

use Larafony\Framework\Events\EventDispatcher;
use Larafony\Framework\Events\ListenerDiscovery;
use Larafony\Framework\Events\ListenerProvider;
use Larafony\Framework\Tests\Events\Fixtures\InvalidListener;
use Larafony\Framework\Tests\Events\Fixtures\OrderPlaced;
use Larafony\Framework\Tests\Events\Fixtures\TestListener;
use Larafony\Framework\Tests\Events\Fixtures\UserRegistered;
use PHPUnit\Framework\TestCase;

final class ListenerDiscoveryTest extends TestCase
{
    public function testDiscoverListeners(): void
    {
        $provider = new ListenerProvider();
        $discovery = new ListenerDiscovery($provider, [TestListener::class]);

        $discovery->discover();

        $userEvent = new UserRegistered('john@example.com', 'John Doe');
        $userListeners = iterator_to_array($provider->getListenersForEvent($userEvent));

        $this->assertCount(1, $userListeners);

        $orderEvent = new OrderPlaced(123, 99.99);
        $orderListeners = iterator_to_array($provider->getListenersForEvent($orderEvent));

        $this->assertCount(2, $orderListeners);
    }

    public function testListenerPriorityFromAttribute(): void
    {
        $events = [];
        $provider = new ListenerProvider();

        // Test that priority works - high priority should execute first
        $provider->listen(
            OrderPlaced::class,
            function (OrderPlaced $event) use (&$events): void {
                $events[] = 'high';
            },
            10
        );

        $provider->listen(
            OrderPlaced::class,
            function (OrderPlaced $event) use (&$events): void {
                $events[] = 'low';
            },
            5
        );

        $dispatcher = new EventDispatcher($provider);

        $event = new OrderPlaced(123, 99.99);
        $dispatcher->dispatch($event);

        $this->assertCount(2, $events);
        $this->assertSame('high', $events[0]);
        $this->assertSame('low', $events[1]);
    }

    public function testInferEventClassFromParameter(): void
    {
        $provider = new ListenerProvider();
        $discovery = new ListenerDiscovery($provider, [TestListener::class]);

        $discovery->discover();

        $event = new OrderPlaced(123, 99.99);
        $listeners = iterator_to_array($provider->getListenersForEvent($event));

        // Should have 2 listeners for OrderPlaced (both inferred from parameter type)
        $this->assertCount(2, $listeners);
    }

    public function testExplicitEventClassInAttribute(): void
    {
        $provider = new ListenerProvider();
        $discovery = new ListenerDiscovery($provider, [TestListener::class]);

        $discovery->discover();

        $event = new UserRegistered('john@example.com', 'John Doe');
        $listeners = iterator_to_array($provider->getListenersForEvent($event));

        // Should have 1 listener for UserRegistered (explicit in attribute)
        $this->assertCount(1, $listeners);
    }

    public function testThrowsExceptionWhenCannotInferEventClass(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot infer event class');

        $provider = new ListenerProvider();
        $discovery = new ListenerDiscovery($provider, [InvalidListener::class]);

        $discovery->discover();
    }
}
