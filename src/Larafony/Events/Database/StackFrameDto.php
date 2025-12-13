<?php

declare(strict_types=1);

namespace Larafony\Framework\Events\Database;

final readonly class StackFrameDto
{
    public function __construct(
        public ?string $file = null,
        public ?int $line = null,
        public ?string $class = null,
        public ?string $function = null,
        public ?string $compiledFile = null,
        public ?int $compiledLine = null,
    ) {
    }

    public function containsPath(string $path): bool
    {
        return $this->file !== null && str_contains($this->file, $path);
    }
}
