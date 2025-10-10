<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Exceptions;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Exception thrown for 400 Bad Request errors.
 */
class BadRequestError extends ClientError
{
    public static function fromResponse(RequestInterface $request, ResponseInterface $response): self
    {
        return new self(
            message: '400 Bad Request',
            method: $request->getMethod(),
            uri: (string) $request->getUri(),
            statusCode: 400,
            reasonPhrase: $response->getReasonPhrase(),
            code: 400,
        );
    }
}
