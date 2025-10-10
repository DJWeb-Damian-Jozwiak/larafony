<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Exceptions;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Exception thrown for 4xx HTTP client errors.
 */
class ClientError extends HttpError
{
    public static function fromResponse(RequestInterface $request, ResponseInterface $response): self
    {
        $statusCode = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();

        return match ($statusCode) {
            400 => BadRequestError::fromResponse($request, $response),
            401 => UnauthorizedError::fromResponse($request, $response),
            403 => ForbiddenError::fromResponse($request, $response),
            404 => NotFoundError::fromResponse($request, $response),
            default => new self(
                message: "Client error {$statusCode}: {$reasonPhrase}",
                method: $request->getMethod(),
                uri: (string) $request->getUri(),
                statusCode: $statusCode,
                reasonPhrase: $reasonPhrase,
                code: $statusCode,
            ),
        };
    }
}
