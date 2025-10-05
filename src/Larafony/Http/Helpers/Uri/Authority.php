<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Helpers\Uri;

class Authority
{
    private const array DEFAULT_PORTS = [
        'http' => 80,
        'https' => 443,
    ];

    private int $defaultPort;

    public function __construct()
    {
        $scheme = Scheme::get();
        $this->defaultPort = self::DEFAULT_PORTS[$scheme] ?? 0;
    }
    public function get(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';

        // HTTP_HOST already contains port if present, so use it as-is
        if (isset($_SERVER['HTTP_HOST'])) {
            return $host;
        }

        // SERVER_NAME doesn't contain port, so we need to add it
        $port = $_SERVER['HTTP_X_FORWARDED_PORT'] ?? $_SERVER['SERVER_PORT'] ?? $this->defaultPort;

        return (int) $port === $this->defaultPort ? $host : $host . ':' . $port;
    }
}
