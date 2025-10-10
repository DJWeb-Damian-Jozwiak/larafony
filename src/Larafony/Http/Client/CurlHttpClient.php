<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client;

use Larafony\Framework\Http\Client\Config\HttpClientConfig;
use Larafony\Framework\Http\Client\Curl\CurlHandleExecutor;
use Larafony\Framework\Http\Client\Curl\CurlOptionsBuilder;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 HTTP Client implementation using PHP's CurlHandle.
 *
 * This is the production HTTP client that makes real network requests.
 * For testing, use MockHttpClient instead.
 *
 * Features:
 * - Based on native PHP CurlHandle (no external dependencies)
 * - Full PSR-18 compliance
 * - Supports all HTTP methods
 * - Handles timeouts, redirects, SSL/TLS, proxy
 * - Proper exception handling
 * - Configurable via HttpClientConfig DTO
 *
 * Example usage:
 * ```php
 * // Default config
 * $client = new CurlHttpClient();
 *
 * // Custom timeout
 * $client = new CurlHttpClient(HttpClientConfig::withTimeout(60));
 *
 * // Insecure (dev)
 * $client = new CurlHttpClient(HttpClientConfig::insecure());
 *
 * // With proxy
 * $client = new CurlHttpClient(HttpClientConfig::withProxy('proxy.local:8080', 'user:pass'));
 * ```
 */
final class CurlHttpClient implements ClientInterface
{
    private readonly CurlHandleExecutor $executor;

    public function __construct(?HttpClientConfig $config = null)
    {
        $config ??= new HttpClientConfig();
        $optionsBuilder = new CurlOptionsBuilder($config);
        $this->executor = new CurlHandleExecutor($optionsBuilder);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->executor->execute($request);
    }
}
