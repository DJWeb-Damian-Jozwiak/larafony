<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Events;

use Larafony\Framework\Events\ListenerProvider;
use Larafony\Framework\Tests\Events\Fixtures\UserRegistered;
use PHPUnit\Framework\TestCase;

final class ListenerProviderTest extends TestCase
{
    public function testListenWithClosure(): void
    {
        $provider = new ListenerProvider();
        $called = false;

        $provider->listen(
            UserRegistered::class,
            function (UserRegistered $event) use (&$called): void {
                $called = true;
            }
        );

        $event = new UserRegistered('john@example.com', 'John Doe');
        $listeners = iterator_to_array($provider->getListenersForEvent($event));

        $this->assertCount(1, $listeners);
        $listeners[0]($event);
        $this->assertTrue($called);
    }

    public function testListenWithArrayCallable(): void
    {
        $provider = new ListenerProvider();

        $provider->listen(
            UserRegistered::class,
            [UserRegisteredListener::class, 'handle']
        );

        $event = new UserRegistered('john@example.com', 'John Doe');
        $listeners = iterator_to_array($provider->getListenersForEvent($event));

        $this->assertCount(1, $listeners);
        $this->assertIsCallable($listeners[0]);
    }

    public function testPriorityOrdering(): void
    {
        $provider = new ListenerProvider();

        $provider->listen(UserRegistered::class, fn() => 'normal', 0);
        $provider->listen(UserRegistered::class, fn() => 'high', 10);
        $provider->listen(UserRegistered::class, fn() => 'low', -10);

        $event = new UserRegistered('john@example.com', 'John Doe');
        $listeners = iterator_to_array($provider->getListenersForEvent($event));

        $this->assertCount(3, $listeners);
        $this->assertSame('high', $listeners[0]());
        $this->assertSame('normal', $listeners[1]());
        $this->assertSame('low', $listeners[2]());
    }

    public function testNoListenersForEvent(): void
    {
        $provider = new ListenerProvider();

        $event = new UserRegistered('john@example.com', 'John Doe');
        $listeners = iterator_to_array($provider->getListenersForEvent($event));

        $this->assertCount(0, $listeners);
    }
}

final class UserRegisteredListener
{
    public function handle(UserRegistered $event): void
    {
        // Do nothing
    }
}
