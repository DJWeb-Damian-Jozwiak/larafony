<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\UploadedFile\Decorators;

use Psr\Http\Message\StreamInterface;

class ErrorValidatorDecorator extends UploadedFileDecorator
{
    public function getStream(): StreamInterface
    {
        $this->validate();
        return parent::getStream();
    }

    public function moveTo(string $targetPath): void
    {
        $this->validate();
        parent::moveTo($targetPath);
    }

    private function validate(): void
    {
        if ($this->getError() !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(
                sprintf('Cannot operate on file due to upload error: %d', $this->getError()),
            );
        }
    }
}
