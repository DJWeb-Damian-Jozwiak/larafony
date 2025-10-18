<?php

declare(strict_types=1);

namespace Larafony\Framework\Log;

use Larafony\Framework\Enums\Log\LogLevel;

final readonly class Message
{
    public function __construct(
        public LogLevel|string $level,
        public string $message,
        public Context $context,
        public ?Metadata $metadata = null
    ) {
    }
}
