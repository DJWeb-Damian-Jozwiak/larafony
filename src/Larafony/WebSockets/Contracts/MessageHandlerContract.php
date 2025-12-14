<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets\Contracts;

use Throwable;

interface MessageHandlerContract
{
    public function onOpen(ConnectionContract $connection): void;

    public function onMessage(ConnectionContract $connection, string $payload): void;

    public function onClose(ConnectionContract $connection): void;

    public function onError(ConnectionContract $connection, Throwable $e): void;
}
