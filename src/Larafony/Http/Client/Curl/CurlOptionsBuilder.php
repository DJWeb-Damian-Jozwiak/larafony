<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Curl;

use Larafony\Framework\Http\Client\Config\HttpClientConfig;
use Psr\Http\Message\RequestInterface;

/**
 * Builds CURL options array from PSR-7 Request.
 *
 * Converts PSR-7 Request object into CURL options (CURLOPT_* constants)
 * that can be passed to curl_setopt_array().
 *
 * Because who the fuck remembers CURL constants? Use HttpClientConfig DTO instead.
 */
final class CurlOptionsBuilder
{
    public function __construct(
        private readonly HttpClientConfig $config = new HttpClientConfig(),
    ) {
    }

    /**
     * Build CURL options array from PSR-7 Request.
     *
     * @param RequestInterface $request
     *
     * @return array<int, mixed> Array of CURLOPT_* => value
     */
    public function build(RequestInterface $request): array
    {
        $options = $this->buildBaseOptions($request);
        $this->addHeaders($options, $request);
        $this->addBody($options, $request);

        return $options;
    }

    /**
     * Build base CURL options from config + request.
     *
     * @return array<int, mixed>
     */
    private function buildBaseOptions(RequestInterface $request): array
    {
        // Use + operator to preserve numeric keys (CURLOPT_* constants)
        // array_merge() would reindex numeric keys - fucking PHP!
        return [
            CURLOPT_URL => (string) $request->getUri(),
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
        ] + $this->config->toCurlOptions();
    }

    /**
     * Add headers to CURL options.
     *
     * @param array<int, mixed> $options
     */
    private function addHeaders(array &$options, RequestInterface $request): void
    {
        $headers = $this->buildHeaders($request);
        if ($headers !== []) {
            $options[CURLOPT_HTTPHEADER] = $headers;
        }
    }

    /**
     * Add body to CURL options.
     *
     * @param array<int, mixed> $options
     */
    private function addBody(array &$options, RequestInterface $request): void
    {
        try {
            $body = (string) $request->getBody();
            if ($body !== '') {
                $options[CURLOPT_POSTFIELDS] = $body;
            }
        } catch (\RuntimeException $e) {
            // Body stream not set - that's OK for GET/HEAD requests
            // Exception intentionally caught and ignored
            unset($e);
        }
    }

    /**
     * Build headers array in CURL format.
     *
     * @return array<int, string> Array of "Header: Value" strings
     */
    private function buildHeaders(RequestInterface $request): array
    {
        $headers = [];

        foreach ($request->getHeaders() as $name => $values) {
            // Capitalize header name properly (e.g., content-type -> Content-Type)
            $capitalizedName = $this->capitalizeHeaderName($name);

            foreach ($values as $value) {
                $headers[] = "{$capitalizedName}: {$value}";
            }
        }

        return $headers;
    }

    /**
     * Capitalize header name according to HTTP conventions.
     * Examples: content-type -> Content-Type, x-custom-header -> X-Custom-Header
     */
    private function capitalizeHeaderName(string $name): string
    {
        return $name
            |> (static fn (string $item): string => str_replace('-', ' ', $item))
            |> ucwords(...)
            |> (static fn (string $item): string => str_replace(' ', '-', $item));
    }
}
