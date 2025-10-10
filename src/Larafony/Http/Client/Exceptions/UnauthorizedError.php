<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Exceptions;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Exception thrown for 401 Unauthorized errors.
 */
class UnauthorizedError extends ClientError
{
    public static function fromResponse(RequestInterface $request, ResponseInterface $response): self
    {
        return new self(
            message: '401 Unauthorized',
            method: $request->getMethod(),
            uri: (string) $request->getUri(),
            statusCode: 401,
            reasonPhrase: $response->getReasonPhrase(),
            code: 401,
        );
    }
}
