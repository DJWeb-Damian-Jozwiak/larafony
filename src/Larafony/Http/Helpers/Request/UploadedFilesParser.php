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
        [,$_FILES] = request_parse_body();
        return self::parse($_FILES);
    }
}
