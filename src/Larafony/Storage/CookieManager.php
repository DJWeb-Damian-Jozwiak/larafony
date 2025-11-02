<?php

declare(strict_types=1);

namespace Larafony\Framework\Storage;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Encryption\EncryptionService;

final class CookieManager
{
    public function set(string $name, mixed $value, CookieOptions $options = new CookieOptions()): void
    {
        $encrypted = new EncryptionService()->encrypt($value);
        //for testing, in real app available in next request
        $_COOKIE[$name] = $encrypted;
        setcookie($name, $encrypted, $options->toArray());
    }

    public function get(string $name, mixed $default = null): mixed
    {
        $value = $_COOKIE[$name] ?? null;

        if ($value === null) {
            return $default;
        }

        return new EncryptionService()->decrypt($value);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $service = new EncryptionService();
        return array_map($service->decrypt(...), $_COOKIE);
    }

    public function remove(string $name): void
    {
        $past = ClockFactory::timestamp() - 3600;
        $_COOKIE[$name] = '';
        setcookie($name, '', new CookieOptions(expires: $past)->toArray());
    }
}
