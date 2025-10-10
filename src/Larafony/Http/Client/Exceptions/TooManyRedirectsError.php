<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Exceptions;

use Psr\Http\Message\RequestInterface;

/**
 * Exception thrown when too many redirects are encountered.
 */
class TooManyRedirectsError extends RequestError
{
    public static function forTooManyRedirects(RequestInterface $request, int $maxRedirects, ?\Throwable $previous = null): self
    {
        return new self(
            message: "Too many redirects encountered (max: {$maxRedirects})",
            method: $request->getMethod(),
            uri: (string) $request->getUri(),
            code: 0,
            previous: $previous,
        );
    }
}
