<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client;

use Larafony\Framework\Http\Client\Contracts\MockHandler;
use Larafony\Framework\Http\Client\Mock\CallbackMockHandler;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP Client factory with strategy pattern for easy testing.
 *
 * Allows swapping client implementation globally for tests without using static methods directly.
 * Similar to ClockFactory pattern.
 *
 * Example usage in production:
 * ```php
 * $client = HttpClientFactory::instance();
 * $response = $client->sendRequest($request);
 * ```
 *
 * Example usage in tests:
 * ```php
 * HttpClientFactory::fake(fn() => new Response(statusCode: 200));
 * // Now all code using HttpClientFactory::instance() will get mocked responses
 * ```
 */
final class HttpClientFactory
{
    private static ?ClientInterface $instance = null;

    /**
     * Get the current HTTP client instance (creates CurlHttpClient if not set).
     */
    public static function instance(): ClientInterface
    {
        return self::$instance ??= new CurlHttpClient();
    }

    /**
     * Set a custom client instance (useful for testing).
     */
    public static function withInstance(ClientInterface $client): void
    {
        self::$instance = $client;
    }

    /**
     * Reset to default CurlHttpClient.
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Create a mock client with callback handler (testing).
     *
     * @param \Closure(RequestInterface): ResponseInterface $callback
     */
    public static function fake(\Closure $callback): MockHttpClient
    {
        $handler = new CallbackMockHandler($callback);
        $client = new MockHttpClient($handler);
        self::$instance = $client;

        return $client;
    }

    /**
     * Create a mock client with custom handler (testing).
     */
    public static function fakeWithHandler(MockHandler $handler): MockHttpClient
    {
        $client = new MockHttpClient($handler);
        self::$instance = $client;

        return $client;
    }

    /**
     * Send a request using the current client instance.
     */
    public static function sendRequest(RequestInterface $request): ResponseInterface
    {
        return self::instance()->sendRequest($request);
    }
}
