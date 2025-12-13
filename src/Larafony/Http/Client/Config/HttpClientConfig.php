<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Config;

/**
 * HTTP Client configuration DTO.
 *
 * Because who remembers CURL constants?
 */
final readonly class HttpClientConfig
{
    public function __construct(
        public int $timeout = 30,
        public int $connectTimeout = 10,
        public bool $followRedirects = true,
        public int $maxRedirects = 10,
        public bool $verifyPeer = true,
        public bool $verifyHost = true,
        public ?string $proxy = null,
        public ?string $proxyAuth = null,
        public int $httpVersion = CURL_HTTP_VERSION_2_0,
    ) {
    }

    /**
     * Create config with custom timeout.
     */
    public static function withTimeout(int $seconds): self
    {
        return new self(timeout: $seconds);
    }

    /**
     * Create config for local development (no SSL verification).
     */
    public static function insecure(): self
    {
        return new self(
            verifyPeer: false,
            verifyHost: false,
        );
    }

    /**
     * Create config with proxy.
     */
    public static function withProxy(string $proxy, ?string $auth = null): self
    {
        return new self(
            proxy: $proxy,
            proxyAuth: $auth,
        );
    }

    /**
     * Create config that doesn't follow redirects.
     */
    public static function noRedirects(): self
    {
        return new self(followRedirects: false);
    }

    /**
     * HTTP/1.1 only (for compatibility).
     */
    public static function http11(): self
    {
        return new self(httpVersion: CURL_HTTP_VERSION_1_1);
    }

    /**
     * Convert to CURL options array.
     *
     * @return array<int, mixed>
     */
    public function toCurlOptions(): array
    {
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_FOLLOWLOCATION => $this->followRedirects,
            CURLOPT_MAXREDIRS => $this->maxRedirects,
            CURLOPT_HTTP_VERSION => $this->httpVersion,
            CURLOPT_SSL_VERIFYPEER => $this->verifyPeer,
            CURLOPT_SSL_VERIFYHOST => $this->verifyHost ? 2 : 0,
        ];

        if ($this->proxy !== null) {
            $options[CURLOPT_PROXY] = $this->proxy;
        }

        if ($this->proxyAuth !== null) {
            $options[CURLOPT_PROXYUSERPWD] = $this->proxyAuth;
        }

        return $options;
    }
}
