<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Exceptions;

use Psr\Http\Message\RequestInterface;

/**
 * Exception thrown when DNS resolution fails.
 */
class DnsError extends NetworkError
{
    public static function couldNotResolve(RequestInterface $request, string $host): self
    {
        return new self(
            message: "Could not resolve host: {$host}",
            method: $request->getMethod(),
            uri: (string) $request->getUri(),
        );
    }
}
