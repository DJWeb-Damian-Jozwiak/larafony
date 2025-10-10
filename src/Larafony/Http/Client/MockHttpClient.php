<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client;

use Larafony\Framework\Http\Client\Contracts\MockHandler;
use Larafony\Framework\Http\Client\Exceptions\HttpClientError;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Mock HTTP Client for testing.
 *
 * This client does not make real network requests.
 * Instead, it uses a MockHandler to return pre-defined responses.
 *
 * This is similar to FrozenClock - a test double for the real implementation.
 *
 * Example usage:
 * ```php
 * $mockHandler = new CallbackMockHandler(fn() => new Response(statusCode: 200));
 * $client = new MockHttpClient($mockHandler);
 * $response = $client->sendRequest($request); // Returns mocked response
 * ```
 */
final class MockHttpClient implements ClientInterface
{
    /**
     * @var array<int, RequestInterface>
     */
    private array $requestHistory = [];

    public function __construct(
        private readonly MockHandler $handler,
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        // Record request for assertions
        $this->requestHistory[] = $request;

        // Check if handler has responses available
        if (! $this->handler->hasResponses()) {
            throw new HttpClientError('Mock handler has no more responses available');
        }

        // Get response from handler
        return $this->handler->handle($request);
    }

    /**
     * Get all requests that were sent through this client.
     *
     * Useful for assertions in tests.
     *
     * @return array<int, RequestInterface>
     */
    public function getRequestHistory(): array
    {
        return $this->requestHistory;
    }

    /**
     * Get the last request that was sent.
     */
    public function getLastRequest(): ?RequestInterface
    {
        if ($this->requestHistory === []) {
            return null;
        }

        $lastKey = array_key_last($this->requestHistory);
        return $this->requestHistory[$lastKey];
    }

    /**
     * Check if any requests were sent.
     */
    public function hasRequests(): bool
    {
        return $this->requestHistory !== [];
    }

    /**
     * Reset request history.
     */
    public function resetHistory(): void
    {
        $this->requestHistory = [];
    }
}
