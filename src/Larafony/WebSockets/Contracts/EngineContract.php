<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets\Contracts;

use Closure;

interface EngineContract
{
    public function listen(string $host, int $port): void;

    public function onConnection(Closure $handler): void;

    public function onData(Closure $handler): void;

    public function onClose(Closure $handler): void;

    public function onError(Closure $handler): void;

    public function run(): void;

    public function stop(): void;
}
