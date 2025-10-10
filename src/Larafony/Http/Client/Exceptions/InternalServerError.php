<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Exceptions;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Exception thrown for 500 Internal Server Error.
 */
class InternalServerError extends ServerError
{
    public static function fromResponse(RequestInterface $request, ResponseInterface $response): self
    {
        return new self(
            message: '500 Internal Server Error',
            method: $request->getMethod(),
            uri: (string) $request->getUri(),
            statusCode: 500,
            reasonPhrase: $response->getReasonPhrase(),
            code: 500,
        );
    }
}
