<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Exceptions;

use Psr\Http\Message\RequestInterface;

/**
 * Exception thrown when a request times out.
 */
class TimeoutError extends RequestError
{
    public static function forTimeout(RequestInterface $request, int $timeoutSeconds, ?\Throwable $previous = null): self
    {
        return new self(
            message: "Request timed out after {$timeoutSeconds} seconds",
            method: $request->getMethod(),
            uri: (string) $request->getUri(),
            code: 0,
            previous: $previous,
        );
    }
}
