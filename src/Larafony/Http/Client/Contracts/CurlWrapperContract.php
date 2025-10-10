<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Contracts;

use CurlHandle;

/**
 * Abstraction over PHP's CURL functions.
 *
 * This interface allows us to:
 * - Mock CURL behavior in tests (FakeCurlWrapper)
 * - Use real CURL in production (CurlWrapper)
 * - Test all error paths without network calls
 *
 * Double Testing pattern: real implementation for production, fake for tests.
 */
interface CurlWrapperContract
{
    /**
     * Initialize CURL session.
     *
     * @return CurlHandle|false
     */
    public function init(): CurlHandle|false;

    /**
     * Set multiple CURL options.
     *
     * @param CurlHandle $curl
     * @param array<int, mixed> $options
     */
    public function withOptArray(CurlHandle $curl, array $options): bool;

    /**
     * Execute CURL request.
     *
     * @return string|bool Raw response or false on failure
     */
    public function exec(CurlHandle $curl): string|bool;

    /**
     * Get CURL error number.
     */
    public function errno(CurlHandle $curl): int;

    /**
     * Get CURL error string.
     */
    public function error(CurlHandle $curl): string;

    /**
     * Get CURL information.
     *
     * @return mixed
     */
    public function getInfo(CurlHandle $curl, int $option): mixed;
}
