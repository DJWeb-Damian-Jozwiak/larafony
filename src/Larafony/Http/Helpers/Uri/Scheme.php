<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Helpers\Uri;

class Scheme
{
    public static function get(): string
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return 'https';
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            return $_SERVER['HTTP_X_FORWARDED_PROTO'];
        }

        return 'http';
    }
}
