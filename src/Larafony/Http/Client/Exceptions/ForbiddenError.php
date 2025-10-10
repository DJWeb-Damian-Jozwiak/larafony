<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Exceptions;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Exception thrown for 403 Forbidden errors.
 */
class ForbiddenError extends ClientError
{
    public static function fromResponse(RequestInterface $request, ResponseInterface $response): self
    {
        return new self(
            message: '403 Forbidden',
            method: $request->getMethod(),
            uri: (string) $request->getUri(),
            statusCode: 403,
            reasonPhrase: $response->getReasonPhrase(),
            code: 403,
        );
    }
}
