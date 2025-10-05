<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\UploadedFile\Decorators;

class PathValidatorDecorator extends UploadedFileDecorator
{
    public function moveTo(string $targetPath): void
    {
        $this->validatePath($targetPath);
        parent::moveTo($targetPath);
    }

    private function validatePath(string $targetPath): void
    {
        if ($targetPath === '') {
            throw new \InvalidArgumentException('Invalid path provided for move operation');
        }

        $targetDirectory = dirname($targetPath);

        if (! is_dir($targetDirectory) || ! is_writable($targetDirectory)) {
            throw new \RuntimeException(
                sprintf('The target directory "%s" does not exist or is not writable', $targetDirectory),
            );
        }
    }
}
