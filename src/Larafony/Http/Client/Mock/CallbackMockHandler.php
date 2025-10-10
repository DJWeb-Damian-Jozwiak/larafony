<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Mock;

use Closure;
use Larafony\Framework\Http\Client\Contracts\MockHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Mock handler that uses a callback to generate responses dynamically.
 *
 * This is the most flexible mocking strategy (Strategy C) as it allows
 * you to inspect the request and return different responses based on:
 * - HTTP method
 * - URL/path
 * - Headers
 * - Request body
 * - Any other request property
 *
 * Example usage:
 * ```php
 * $handler = new CallbackMockHandler(function (RequestInterface $request) {
 *     if ($request->getMethod() === 'POST') {
 *         return new Response(201, [], json_encode(['created' => true]));
 *     }
 *     return new Response(200, [], json_encode(['data' => 'test']));
 * });
 * ```
 */
final class CallbackMockHandler implements MockHandler
{
    /**
     * @param Closure(RequestInterface): ResponseInterface $callback
     */
    public function __construct(
        private readonly Closure $callback,
    ) {
    }

    public function handle(RequestInterface $request): ResponseInterface
    {
        return ($this->callback)($request);
    }

    /**
     * Callback handlers always have responses available.
     */
    public function hasResponses(): bool
    {
        return true;
    }

    /**
     * Callback handlers don't maintain state, so reset is a no-op.
     */
    public function reset(): void
    {
        // No state to reset
    }
}
