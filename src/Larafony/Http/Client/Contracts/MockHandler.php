<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Contracts;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Mock handler interface for testing HTTP clients.
 *
 * Implementations can return pre-defined responses, sequences,
 * or use callbacks to dynamically generate responses based on requests.
 */
interface MockHandler
{
    /**
     * Handle a mock request and return a response.
     *
     * @param RequestInterface $request The HTTP request to mock
     *
     * @return ResponseInterface The mocked response
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface If the mock encounters an error
     */
    public function handle(RequestInterface $request): ResponseInterface;

    /**
     * Check if this handler has more responses to return.
     *
     * @return bool True if more responses are available
     */
    public function hasResponses(): bool;

    /**
     * Reset the handler to its initial state.
     */
    public function reset(): void;
}
