<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Factories;

use Larafony\Framework\Http\UploadedFile;
use Larafony\Framework\Http\UploadedFile\Decorators\ErrorValidatorDecorator;
use Larafony\Framework\Http\UploadedFile\Decorators\MoveStatusDecorator;
use Larafony\Framework\Http\UploadedFile\Decorators\PathValidatorDecorator;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

final readonly class UploadedFileFactory implements UploadedFileFactoryInterface
{
    public function createUploadedFile(
        StreamInterface $stream,
        ?int $size = null,
        int $error = \UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null,
    ): UploadedFileInterface {
        return $this->create($stream, $size ?? 0, $error, $clientFilename, $clientMediaType);
    }

    public static function create(
        StreamInterface|string $streamOrFile,
        int $size,
        int $error,
        ?string $clientFilename = null,
        ?string $clientMediaType = null,
    ): UploadedFileInterface {
        $file = new UploadedFile($streamOrFile, $size, $error, $clientFilename, $clientMediaType);
        $file = new ErrorValidatorDecorator($file);
        $file = new MoveStatusDecorator($file);

        return new PathValidatorDecorator($file);
    }
}
