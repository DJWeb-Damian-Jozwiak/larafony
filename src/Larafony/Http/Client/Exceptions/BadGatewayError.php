<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Exceptions;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Exception thrown for 502 Bad Gateway errors.
 */
class BadGatewayError extends ServerError
{
    public static function fromResponse(RequestInterface $request, ResponseInterface $response): self
    {
        return new self(
            message: '502 Bad Gateway',
            method: $request->getMethod(),
            uri: (string) $request->getUri(),
            statusCode: 502,
            reasonPhrase: $response->getReasonPhrase(),
            code: 502,
        );
    }
}
