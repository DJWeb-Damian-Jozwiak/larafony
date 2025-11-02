<?php

declare(strict_types=1);

namespace Larafony\Framework\Storage;

use Larafony\Framework\Clock\ClockFactory;

final readonly class CookieOptions
{
    public int $expires;
    public bool $secure;
    public function __construct(
        int $expires = 0,
        public string $path = '/',
        public string $domain = '',
        ?bool $secure = null,
        public bool $httponly = true,
        public string $samesite = 'Lax',
    ) {
        $secure ??= isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $this->secure = $secure;
        $this->expires = $expires ? $expires : ClockFactory::timestamp() + 3600;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'expires' => $this->expires,
            'path' => $this->path,
            'domain' => $this->domain,
            'secure' => $this->secure,
            'httponly' => $this->httponly,
            'samesite' => $this->samesite,
        ];
    }
}
