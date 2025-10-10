<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Exceptions;

use Psr\Http\Message\RequestInterface;

/**
 * Exception thrown when a connection to the server fails.
 */
class ConnectionError extends NetworkError
{
    public static function couldNotConnect(RequestInterface $request, string $host): self
    {
        return new self(
            message: "Could not connect to host: {$host}",
            method: $request->getMethod(),
            uri: (string) $request->getUri(),
        );
    }

    public static function sslError(RequestInterface $request, string $message): self
    {
        return new self(
            message: "SSL/TLS error: {$message}",
            method: $request->getMethod(),
            uri: (string) $request->getUri(),
        );
    }
}
