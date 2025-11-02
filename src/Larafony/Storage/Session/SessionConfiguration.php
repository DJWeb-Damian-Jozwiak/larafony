<?php

declare(strict_types=1);

namespace Larafony\Framework\Storage\Session;

use InvalidArgumentException;
use SessionHandlerInterface;

final class SessionConfiguration
{
    /**
     * @var array<string, SessionHandlerInterface>
     */
    private array $handlers = [];

    public function registerHandler(SessionHandlerInterface $handler): self
    {
        $this->handlers[$handler::class] = $handler;

        return $this;
    }

    public function getHandler(string $handler): SessionHandlerInterface
    {
        $exception = new InvalidArgumentException("Session handler {$handler} not found");
        return $this->handlers[$handler] ?? throw $exception;
    }
}
