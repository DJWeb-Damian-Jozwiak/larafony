<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Exceptions;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Exception thrown for 404 Not Found errors.
 */
class NotFoundError extends ClientError
{
    public static function fromResponse(RequestInterface $request, ResponseInterface $response): self
    {
        return new self(
            message: '404 Not Found',
            method: $request->getMethod(),
            uri: (string) $request->getUri(),
            statusCode: 404,
            reasonPhrase: $response->getReasonPhrase(),
            code: 404,
        );
    }
}
