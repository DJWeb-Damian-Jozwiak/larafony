<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Helpers\Request;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Http\Factories\UploadedFileFactory;
use Psr\Http\Message\UploadedFileInterface;

final readonly class UploadedFilesParser
{
    /**
     * @param array<string, mixed> $files
     *
     * @return array<string, UploadedFileInterface>
     */
    public static function parse(array $files): array
    {
        $parsed = [];
        $files = array_filter($files, static fn (mixed $file): bool => is_array($file));

        foreach ($files as $key => $file) {
            $stream = new StreamFactory()->createStreamFromFile($file['tmp_name'] ?? '');
            $parsed[$key] = new UploadedFileFactory()->createUploadedFile(
                $stream,
                $file['size'] ?? 0,
                $file['error'] ?? UPLOAD_ERR_NO_FILE,
                $file['name'] ?? null,
                $file['type'] ?? null,
            );
        }

        return $parsed;
    }

    /**
     * @return array<string, UploadedFileInterface>
     */
    public static function parseFromGlobals(): array
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return [];
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        $contentType = strtolower(trim(explode(';', $contentType)[0]));

        // request_parse_body() only supports multipart/form-data and application/x-www-form-urlencoded
        if (!in_array($contentType, ['multipart/form-data', 'application/x-www-form-urlencoded', ''], true)) {
            return [];
        }

        [, $_FILES ] = request_parse_body();
        return self::parse($_FILES);
    }
}
