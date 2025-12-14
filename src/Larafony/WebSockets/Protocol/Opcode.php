<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets\Protocol;

enum Opcode: int
{
    case CONTINUATION = 0;
    case TEXT = 1;
    case BINARY = 2;
    case CLOSE = 8;
    case PING = 9;
    case PONG = 10;

    public function isControl(): bool
    {
        return $this->value >= 8;
    }
}
