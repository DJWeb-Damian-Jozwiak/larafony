<?php

declare(strict_types=1);

namespace Larafony\Framework\Web;

use Larafony\Framework\Config\Contracts\ConfigContract;

final class Config
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return Application::instance()->get(ConfigContract::class)->get($key, $default);
    }

    public static function set(string $key, mixed $value): void
    {
        Application::instance()->get(ConfigContract::class)->set($key, $value);
    }
}
