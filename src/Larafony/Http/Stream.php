<?php

declare(strict_types=1);

namespace Larafony\Framework\Http;

use Larafony\Framework\Core\Support\InactivePropertyGuard;
use Larafony\Framework\Http\Factories\StreamMetaDataFactory;
use Larafony\Framework\Http\Helpers\Stream\StreamContentManager;
use Larafony\Framework\Http\Helpers\Stream\StreamMetaData;
use Larafony\Framework\Http\Helpers\Stream\StreamSize;
use Larafony\Framework\Http\Helpers\Stream\StreamWrapper;
use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    private const string MESSAGE = 'Operation not allowed in detached stream';

    private StreamSize $_size;
    private StreamSize $size {
        get => InactivePropertyGuard::get($this->_size, $this->detached, self::MESSAGE);
        set => $this->_size = $value;
    }

    private StreamContentManager $_contentManager;
    private StreamContentManager $contentManager {
        get => InactivePropertyGuard::get($this->_contentManager, $this->detached, self::MESSAGE);
        set => $this->_contentManager = $value;
    }

    private StreamMetaData $_metaData;
    private StreamMetaData $metaData {
        get => InactivePropertyGuard::get($this->_metaData, $this->detached, self::MESSAGE);
        set => $this->_metaData = $value;
    }

    private StreamWrapper $_wrapper;
    private StreamWrapper $wrapper {
        get => InactivePropertyGuard::get($this->_wrapper, $this->detached, self::MESSAGE);
        set => $this->_wrapper = $value;
    }

    private bool $detached = false;

    public function __construct(
        StreamWrapper $wrapper,
    ) {
        $this->wrapper = $wrapper;
        $this->contentManager = new StreamContentManager($this->wrapper);
        $this->size = new StreamSize($this->wrapper);
        $this->metaData = StreamMetaDataFactory::fromStream($this->wrapper);
    }

    public function __toString(): string
    {
        return $this->contentManager->content;
    }

    public function close(): void
    {
        $this->wrapper->close();
    }

    public function detach(): mixed
    {
        if ($this->detached || $this->wrapper->closed) {
            return null;
        }
        $res = $this->wrapper->stream;
        $this->detached = true;
        return $res;
    }

    public function getSize(): ?int
    {
        return $this->size->size;
    }

    public function tell(): int
    {
        return $this->size->tell();
    }

    public function eof(): bool
    {
        return $this->wrapper->eof();
    }

    public function isSeekable(): bool
    {
        return $this->metaData->seekable;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->wrapper->seek($offset, $whence);
    }

    public function rewind(): void
    {
        $this->wrapper->rewind();
    }

    public function isWritable(): bool
    {
        return $this->metaData->writeable;
    }

    public function write(string $string): int
    {
        return $this->contentManager->write($string);
    }

    public function isReadable(): bool
    {
        return $this->metaData->readable;
    }

    public function read(int $length): string
    {
        return $this->contentManager->read($length);
    }

    public function getContents(): string
    {
        return $this->contentManager->content;
    }

    public function getMetadata(?string $key = null): mixed
    {
        return $key ? $this->metaData->get($key) : $this->metaData;
    }
}
