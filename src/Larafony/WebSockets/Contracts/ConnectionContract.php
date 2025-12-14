<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets\Contracts;

use Larafony\Framework\WebSockets\Protocol\Frame;

interface ConnectionContract
{
    public function getId(): string;

    public function send(string|Frame $data): void;

    public function close(int $code = 1000, string $reason = ''): void;

    public function isConnected(): bool;

    public function getRemoteAddress(): string;
}
