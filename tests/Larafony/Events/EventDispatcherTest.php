<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Events;

use Larafony\Framework\Events\EventDispatcher;
use Larafony\Framework\Events\ListenerProvider;
use Larafony\Framework\Tests\Events\Fixtures\OrderPlaced;
use Larafony\Framework\Tests\Events\Fixtures\UserRegistered;
use PHPUnit\Framework\TestCase;

final class EventDispatcherTest extends TestCase
{
    public function testDispatchEvent(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $called = false;
        $receivedEvent = null;

        $provider->listen(
            UserRegistered::class,
            function (UserRegistered $event) use (&$called, &$receivedEvent): void {
                $called = true;
                $receivedEvent = $event;
            }
        );

        $event = new UserRegistered('john@example.com', 'John Doe');
        $result = $dispatcher->dispatch($event);

        $this->assertTrue($called);
        $this->assertSame($event, $result);
        $this->assertSame($event, $receivedEvent);
    }

    public function testMultipleListeners(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $calls = [];

        $provider->listen(
            UserRegistered::class,
            function (UserRegistered $event) use (&$calls): void {
                $calls[] = 'listener1';
            }
        );

        $provider->listen(
            UserRegistered::class,
            function (UserRegistered $event) use (&$calls): void {
                $calls[] = 'listener2';
            }
        );

        $event = new UserRegistered('john@example.com', 'John Doe');
        $dispatcher->dispatch($event);

        $this->assertCount(2, $calls);
        $this->assertSame(['listener1', 'listener2'], $calls);
    }

    public function testListenerPriority(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $calls = [];

        $provider->listen(
            UserRegistered::class,
            function () use (&$calls): void {
                $calls[] = 'normal';
            },
            0
        );

        $provider->listen(
            UserRegistered::class,
            function () use (&$calls): void {
                $calls[] = 'high';
            },
            10
        );

        $provider->listen(
            UserRegistered::class,
            function () use (&$calls): void {
                $calls[] = 'low';
            },
            -10
        );

        $event = new UserRegistered('john@example.com', 'John Doe');
        $dispatcher->dispatch($event);

        $this->assertSame(['high', 'normal', 'low'], $calls);
    }

    public function testStoppableEvent(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $calls = [];

        $provider->listen(
            OrderPlaced::class,
            function (OrderPlaced $event) use (&$calls): void {
                $calls[] = 'listener1';
                $event->stopPropagation();
            },
            10
        );

        $provider->listen(
            OrderPlaced::class,
            function (OrderPlaced $event) use (&$calls): void {
                $calls[] = 'listener2';
            },
            5
        );

        $event = new OrderPlaced(123, 99.99);
        $dispatcher->dispatch($event);

        $this->assertCount(1, $calls);
        $this->assertSame(['listener1'], $calls);
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testNoListenersForEvent(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $event = new UserRegistered('john@example.com', 'John Doe');
        $result = $dispatcher->dispatch($event);

        $this->assertSame($event, $result);
    }
}
