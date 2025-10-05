<?php

declare(strict_types=1);

namespace Larafony\Framework\Http;

use Larafony\Framework\Http\Contracts\UploadedFile\MoveHandler;
use Larafony\Framework\Http\UploadedFile\Handlers\FileMoveHandler;
use Larafony\Framework\Http\UploadedFile\Handlers\StreamMoveHandler;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

readonly class UploadedFile implements UploadedFileInterface
{
    private MoveHandler $handler;

    public function __construct(
        StreamInterface|string $streamOrFile,
        private int $size,
        private int $error,
        private ?string $clientFilename = null,
        private ?string $clientMediaType = null,
    ) {
        $this->handler = is_string($streamOrFile)
            ? new FileMoveHandler($streamOrFile)
            : new StreamMoveHandler($streamOrFile);
    }

    public function moveTo(string $targetPath): void
    {
        $this->handler->moveTo($targetPath);
    }

    public function getStream(): StreamInterface
    {
        return $this->handler->getStream();
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }
}
