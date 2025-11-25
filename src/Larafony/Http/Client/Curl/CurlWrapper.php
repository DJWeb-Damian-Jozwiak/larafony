<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Curl;

use CurlHandle;
use Larafony\Framework\Http\Client\Contracts\CurlWrapperContract;

/**
 * Production CURL wrapper - delegates to real PHP CURL functions.
 *
 * This is a thin wrapper that maps interface methods to global CURL functions.
 * No logic here - just delegation.
 */
final class CurlWrapper implements CurlWrapperContract
{
    public function init(): CurlHandle
    {
        $handle = curl_init();
        return $handle === false ? throw new \RuntimeException('Failed to initialize CURL handle') : $handle;
    }

    public function withOptArray(CurlHandle $curl, array $options): bool
    {
        return curl_setopt_array($curl, $options);
    }

    public function exec(CurlHandle $curl): string|bool
    {
        return curl_exec($curl);
    }

    public function errno(CurlHandle $curl): int
    {
        return curl_errno($curl);
    }

    public function error(CurlHandle $curl): string
    {
        return curl_error($curl);
    }

    public function getInfo(CurlHandle $curl, int $option): mixed
    {
        return curl_getinfo($curl, $option);
    }
}
