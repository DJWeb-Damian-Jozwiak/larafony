<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Helpers\Request;

final readonly class HeadersParser
{
    /**
     * @param array<string, mixed> $server
     *
     * @return array<string, string>
     */
    public static function parse(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (! str_starts_with($key, 'HTTP_')) {
                continue;
            }

            $name = substr($key, 5) //remove HTTP_ prefix
                        |> (static fn (string $item) => str_replace('_', ' ', $item))
                        |> strtolower(...)
                        |> ucwords(...)
                        |> (static fn (string $item) => str_replace(' ', '-', $item));

            $headers[$name] = (string) $value;
        }

        return $headers;
    }

    /**
     * @return array<string, string>
     */
    public static function parseFromGlobals(): array
    {
        return self::parse($_SERVER);
    }
}
