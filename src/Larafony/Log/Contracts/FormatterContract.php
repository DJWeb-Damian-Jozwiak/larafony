<?php

declare(strict_types=1);

namespace Larafony\Framework\Log\Contracts;

use Larafony\Framework\Log\Message;

interface FormatterContract
{
    public function format(Message $message): string;
}
