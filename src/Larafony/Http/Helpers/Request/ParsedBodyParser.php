<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Helpers\Request;

final readonly class ParsedBodyParser
{
    /**
     * @return ?array<string, mixed>
     *
     * @throws \JsonException
     */
    public static function parse(string $contentType, string $method, string $input): ?array
    {
        if ($method !== 'POST') {
            return null;
        }

        if (str_contains($contentType, 'application/json')) {
            return $input !== '' ? json_decode($input, true, flags: JSON_THROW_ON_ERROR) : null;
        }

        return $_POST;
    }

    /**
     * @return ?array<string, mixed>
     *
     * @throws \JsonException
     */
    public static function parseFromGlobals(): ?array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $contents = file_get_contents('php://input');
        $input = is_string($contents) ? $contents : '';

        return self::parse($contentType, $method, $input);
    }
}
