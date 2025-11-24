<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Testing;

use CurlHandle;
use Larafony\Framework\Http\Client\Contracts\CurlWrapperContract;

/**
 * Fake CURL wrapper for testing.
 *
 * Allows us to simulate:
 * - Different CURL error codes (timeout, DNS, SSL, etc.)
 * - Different responses
 * - Network failures
 *
 * Without making real network calls.
 */
final class FakeCurlWrapper implements CurlWrapperContract
{
    private int $errno = 0;
    private string $error = '';
    private string|bool $response = '';
    private int $headerSize = 0;

    /**
     * Configure fake to return specific error.
     */
    public function withError(int $errno, string $error): self
    {
        $this->errno = $errno;
        $this->error = $error;
        return $this;
    }

    /**
     * Configure fake to return specific response.
     */
    public function withResponse(string $response, int $headerSize = 0): self
    {
        $this->response = $response;
        $this->headerSize = $headerSize;
        return $this;
    }

    /**
     * Configure fake to fail (return false).
     */
    public function withFailure(): self
    {
        $this->response = false;
        return $this;
    }

    public function init(): CurlHandle
    {
        $handle = curl_init();
        return $handle === false ? throw new \RuntimeException('Failed to initialize CURL handle') : $handle;
    }

    public function withOptArray(CurlHandle $curl, array $options): bool
    {
        // Just return true - we don't actually configure anything
        return true;
    }

    public function exec(CurlHandle $curl): string|bool
    {
        return $this->response;
    }

    public function errno(CurlHandle $curl): int
    {
        return $this->errno;
    }

    public function error(CurlHandle $curl): string
    {
        return $this->error;
    }

    public function getInfo(CurlHandle $curl, int $option): mixed
    {
        return $option === CURLINFO_HEADER_SIZE ? $this->headerSize : null;
    }
}
