<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets\Contracts;

use Closure;
use SplObjectStorage;

interface ServerContract
{
    public function on(string $event, callable $callback): void;

    public function broadcast(string $message, ?Closure $filter = null): void;

    /**
     * @return SplObjectStorage<ConnectionContract, bool>
     */
    public function getConnections(): SplObjectStorage;

    public function run(): void;

    public function stop(): void;
}
