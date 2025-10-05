<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\UploadedFile\Decorators;

class MoveStatusDecorator extends UploadedFileDecorator
{
    private bool $moved = false;

    public function moveTo(string $targetPath): void
    {
        if ($this->moved) {
            throw new \RuntimeException('Cannot move file; already moved!');
        }

        parent::moveTo($targetPath);
        $this->moved = true;
    }
}
