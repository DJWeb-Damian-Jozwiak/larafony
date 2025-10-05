<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Contracts\UploadedFile;

use Psr\Http\Message\StreamInterface;

interface MoveHandler
{
    public function moveTo(string $targetPath): void;
    public function getStream(): StreamInterface;
}
