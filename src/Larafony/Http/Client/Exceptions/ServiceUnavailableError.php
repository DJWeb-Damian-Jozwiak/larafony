<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Exceptions;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Exception thrown for 503 Service Unavailable errors.
 */
class ServiceUnavailableError extends ServerError
{
    public static function fromResponse(RequestInterface $request, ResponseInterface $response): self
    {
        return new self(
            message: '503 Service Unavailable',
            method: $request->getMethod(),
            uri: (string) $request->getUri(),
            statusCode: 503,
            reasonPhrase: $response->getReasonPhrase(),
            code: 503,
        );
    }
}
