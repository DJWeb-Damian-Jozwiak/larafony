<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Exceptions;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Exception for when a request fails.
 *
 * This can be due to:
 * - Invalid request (malformed URL, invalid headers)
 * - Request timeout
 * - Too many redirects
 * - Other runtime request errors
 *
 * Implements PSR-18 RequestExceptionInterface.
 *
 * Note: Stores only request metadata (method, URI), not the full Request object
 * to avoid memory leaks and serialization issues.
 */
class RequestError extends HttpClientError implements RequestExceptionInterface
{
    public function __construct(
        string $message,
        private readonly string $method,
        private readonly string $uri,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create from PSR-7 Request.
     */
    public static function fromRequest(string $message, RequestInterface $request, int $code = 0, ?\Throwable $previous = null): self
    {
        return new self(
            message: $message,
            method: $request->getMethod(),
            uri: (string) $request->getUri(),
            code: $code,
            previous: $previous,
        );
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * PSR-18 requires this method, but we can't return the original Request object.
     * This is a limitation of storing only scalars instead of the full object.
     *
     * @deprecated Use getMethod() and getUri() instead
     * @throws \RuntimeException Always throws as we don't store the full Request
     */
    public function getRequest(): RequestInterface
    {
        throw new \RuntimeException(
            'Request object is not available. Use getMethod() and getUri() instead. '
            . 'Original request: ' . $this->method . ' ' . $this->uri
        );
    }
}
