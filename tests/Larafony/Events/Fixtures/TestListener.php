<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Events\Fixtures;

use Larafony\Framework\Events\Attributes\Listen;

final class TestListener
{
    public array $events = [];

    #[Listen(UserRegistered::class)]
    public function onUserRegistered(UserRegistered $event): void
    {
        $this->events[] = ['type' => 'user_registered', 'email' => $event->email];
    }

    #[Listen(priority: 10)]
    public function onOrderPlacedHigh(OrderPlaced $event): void
    {
        $this->events[] = ['type' => 'order_placed_high', 'orderId' => $event->orderId];
    }

    #[Listen(priority: 5)]
    public function onOrderPlacedLow(OrderPlaced $event): void
    {
        $this->events[] = ['type' => 'order_placed_low', 'orderId' => $event->orderId];
    }
}
