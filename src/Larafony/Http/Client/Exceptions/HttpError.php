<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Exceptions;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Exception thrown for HTTP errors (4xx and 5xx status codes).
 *
 * This is NOT part of PSR-18 specification, but a convenience
 * for handling HTTP-level errors in a structured way.
 *
 * Note: Stores only essential data (method, URI, status code, reason phrase),
 * not full Request/Response objects to avoid memory leaks.
 */
class HttpError extends HttpClientError
{
    public function __construct(
        string $message,
        private readonly string $method,
        private readonly string $uri,
        private readonly int $statusCode,
        private readonly ?string $reasonPhrase = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getReasonPhrase(): ?string
    {
        return $this->reasonPhrase;
    }

    public static function fromResponse(RequestInterface $request, ResponseInterface $response): self
    {
        $statusCode = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();

        return match (true) {
            $statusCode >= 400 && $statusCode < 500 => ClientError::fromResponse($request, $response),
            $statusCode >= 500 => ServerError::fromResponse($request, $response),
            default => new self(
                message: "HTTP error {$statusCode}: {$reasonPhrase}",
                method: $request->getMethod(),
                uri: (string) $request->getUri(),
                statusCode: $statusCode,
                reasonPhrase: $reasonPhrase,
                code: $statusCode,
            ),
        };
    }
}
