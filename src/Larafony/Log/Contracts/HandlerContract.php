<?php

declare(strict_types=1);

namespace Larafony\Framework\Log\Contracts;

use Larafony\Framework\Log\Message;

interface HandlerContract
{
    public function handle(Message $message): void;
}
