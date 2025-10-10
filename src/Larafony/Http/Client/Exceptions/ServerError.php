<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Exceptions;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Exception thrown for 5xx HTTP server errors.
 */
class ServerError extends HttpError
{
    public static function fromResponse(RequestInterface $request, ResponseInterface $response): self
    {
        $statusCode = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();

        return match ($statusCode) {
            500 => InternalServerError::fromResponse($request, $response),
            502 => BadGatewayError::fromResponse($request, $response),
            503 => ServiceUnavailableError::fromResponse($request, $response),
            default => new self(
                message: "Server error {$statusCode}: {$reasonPhrase}",
                method: $request->getMethod(),
                uri: (string) $request->getUri(),
                statusCode: $statusCode,
                reasonPhrase: $reasonPhrase,
                code: $statusCode,
            ),
        };
    }
}
