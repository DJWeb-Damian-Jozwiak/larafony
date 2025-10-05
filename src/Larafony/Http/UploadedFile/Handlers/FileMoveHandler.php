<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\UploadedFile\Handlers;

use Larafony\Framework\Http\Contracts\UploadedFile\MoveHandler;
use Larafony\Framework\Http\Factories\StreamFactory;
use Psr\Http\Message\StreamInterface;

readonly class FileMoveHandler implements MoveHandler
{
    public function __construct(
        private string $filePath,
        private StreamFactory $streamFactory = new StreamFactory(),
    ) {
    }

    public function moveTo(string $targetPath): void
    {
        PHP_SAPI === 'cli' ? rename($this->filePath, $targetPath) : move_uploaded_file($this->filePath, $targetPath);
    }

    public function getStream(): StreamInterface
    {
        return $this->streamFactory->createStreamFromFile($this->filePath, 'r');
    }
}
