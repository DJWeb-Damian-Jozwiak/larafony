<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Exceptions;

use Psr\Http\Message\RequestInterface;

/**
 * Exception thrown when a request is invalid.
 *
 * This includes:
 * - Malformed URLs
 * - Invalid headers
 * - Invalid HTTP method
 * - Other request validation errors
 */
class InvalidRequestError extends RequestError
{
    public static function malformedUrl(RequestInterface $request, string $url): self
    {
        return new self(
            message: "Malformed URL: {$url}",
            method: $request->getMethod(),
            uri: (string) $request->getUri(),
        );
    }

    public static function invalidMethod(RequestInterface $request, string $method): self
    {
        return new self(
            message: "Invalid HTTP method: {$method}",
            method: $request->getMethod(),
            uri: (string) $request->getUri(),
        );
    }
}
