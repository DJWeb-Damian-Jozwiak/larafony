<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail\Transport\Assert;

use Larafony\Framework\Mail\Exceptions\TransportError;
use Larafony\Framework\Mail\Transport\SmtpConnection;

class NotConnected
{
    public static function assertConnection(?SmtpConnection $connection): void
    {
        if ($connection === null || ! $connection->isConnected) {
            throw new TransportError('Not connected to SMTP server');
        }
    }
}
