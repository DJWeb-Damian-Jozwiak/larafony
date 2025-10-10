<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Curl;

use CurlHandle;
use Larafony\Framework\Http\Client\Contracts\CurlWrapperContract;
use Larafony\Framework\Http\Client\Exceptions\ConnectionError;
use Larafony\Framework\Http\Client\Exceptions\DnsError;
use Larafony\Framework\Http\Client\Exceptions\NetworkError;
use Larafony\Framework\Http\Client\Exceptions\TimeoutError;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Executes HTTP requests using PHP's CurlHandle.
 *
 * This class is responsible for:
 * - Creating and configuring CurlHandle instances
 * - Executing the actual CURL request
 * - Converting CURL responses to PSR-7 Response objects
 * - Handling CURL errors and converting them to proper exceptions
 *
 * Uses CurlWrapperContract for testability (Double Testing pattern).
 */
final class CurlHandleExecutor
{
    public function __construct(
        private readonly CurlOptionsBuilder $optionsBuilder = new CurlOptionsBuilder(),
        private readonly ResponseParser $responseParser = new ResponseParser(),
        private readonly CurlWrapperContract $curlWrapper = new CurlWrapper(),
    ) {
    }

    /**
     * Execute an HTTP request using CURL.
     *
     * @throws NetworkError If network issues occur
     * @throws TimeoutError If request times out
     */
    public function execute(RequestInterface $request): ResponseInterface
    {
        $curl = $this->curlWrapper->init();

        if (! $curl instanceof CurlHandle) {
            throw NetworkError::fromRequest('Failed to initialize CURL', $request);
        }

        try {
            // Configure CURL with request data
            $options = $this->optionsBuilder->build($request);
            $this->curlWrapper->withOptArray($curl, $options);

            // Execute request
            $rawResponse = $this->curlWrapper->exec($curl);

            // Check for errors
            $errno = $this->curlWrapper->errno($curl);
            $this->handleCurlError($errno, $this->curlWrapper->error($curl), $request);

            // Parse response
            if ($rawResponse === false) {
                throw NetworkError::fromRequest('CURL execution failed', $request);
            }

            $headerSize = $this->curlWrapper->getInfo($curl, CURLINFO_HEADER_SIZE);
            return $this->responseParser->parse($rawResponse, $curl, $headerSize);
        } finally {
            // curl_close() is no-op since PHP 8.0 and deprecated in 8.5
            // CurlHandle is automatically closed when it goes out of scope
            unset($curl);
        }
    }

    /**
     * Convert CURL error codes to appropriate exceptions.
     *
     * @throws NetworkError
     * @throws TimeoutError
     * @throws ConnectionError
     * @throws DnsError
     */
    private function handleCurlError(int $errno, string $error, RequestInterface $request): void
    {
        if ($errno === 0) {
            return;
        }
        $host = $request->getUri()->getHost();

        throw match (true) {
            $this->isTimeoutError($errno) => TimeoutError::forTimeout($request, 0),
            $this->isDnsError($errno) => DnsError::couldNotResolve($request, $host),
            $this->isConnectionError($errno) => ConnectionError::couldNotConnect($request, $host),
            $this->isSslError($errno) => ConnectionError::sslError($request, $error),
            default => NetworkError::fromRequest("CURL error ({$errno}): {$error}", $request),
        };
    }

    private function isTimeoutError(int $errno): bool
    {
        // CURLE_OPERATION_TIMEDOUT = 28
        return $errno === 28;
    }

    private function isDnsError(int $errno): bool
    {
        return $errno === CURLE_COULDNT_RESOLVE_HOST;
    }

    private function isConnectionError(int $errno): bool
    {
        return $errno === CURLE_COULDNT_CONNECT;
    }

    private function isSslError(int $errno): bool
    {
        return $errno === CURLE_SSL_CONNECT_ERROR || $errno === CURLE_SSL_CERTPROBLEM;
    }
}
