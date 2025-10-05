<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Helpers\Request;

use Psr\Http\Message\UploadedFileInterface;

final readonly class UploadedFilesManager
{
    /**
     * @var array<string, UploadedFileInterface> $uploadedFiles
     */
    public array $uploadedFiles;

    /**
     * @param array<string, mixed> $files
     */
    public function __construct(array $files = [])
    {
        $this->uploadedFiles = $this->validateUploadedFiles($files);
    }

    /**
     * @param array<string, mixed> $uploadedFiles
     */
    public function withUploadedFiles(array $uploadedFiles): self
    {
        return new self($uploadedFiles);
    }

    /**
     * @param array<string, mixed> $files
     *
     * @return array<string, UploadedFileInterface>
     */
    private function validateUploadedFiles(array $files): array
    {
        return array_filter(
            $files,
            static fn (mixed $file): bool => $file instanceof UploadedFileInterface,
        );
    }
}
