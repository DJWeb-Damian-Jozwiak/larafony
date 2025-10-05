<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\UploadedFile\Decorators;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

abstract class UploadedFileDecorator implements UploadedFileInterface
{
    public function __construct(
        protected readonly UploadedFileInterface $file,
    ) {
    }

    public function getStream(): StreamInterface
    {
        return $this->file->getStream();
    }

    public function moveTo(string $targetPath): void
    {
        $this->file->moveTo($targetPath);
    }

    public function getSize(): ?int
    {
        return $this->file->getSize();
    }

    public function getError(): int
    {
        return $this->file->getError();
    }

    public function getClientFilename(): ?string
    {
        return $this->file->getClientFilename();
    }

    public function getClientMediaType(): ?string
    {
        return $this->file->getClientMediaType();
    }
}
