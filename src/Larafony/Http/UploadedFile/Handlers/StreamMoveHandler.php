<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\UploadedFile\Handlers;

use Larafony\Framework\Http\Contracts\UploadedFile\MoveHandler;
use Larafony\Framework\Http\Factories\StreamFactory;
use Psr\Http\Message\StreamInterface;

readonly class StreamMoveHandler implements MoveHandler
{
    public function __construct(
        private StreamInterface $stream,
        private StreamFactory $streamFactory = new StreamFactory(),
    ) {
    }

    public function moveTo(string $targetPath): void
    {
        $destination = $this->streamFactory->createStreamFromFile($targetPath, 'w');

        while (! $this->stream->eof()) {
            $destination->write($this->stream->read(8192));
        }

        $destination->close();
    }

    public function getStream(): StreamInterface
    {
        return $this->stream;
    }
}
