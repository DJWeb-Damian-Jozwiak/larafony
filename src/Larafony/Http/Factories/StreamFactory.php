<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Factories;

use Larafony\Framework\Http\Helpers\Stream\StreamWrapper;
use Larafony\Framework\Http\Stream;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class StreamFactory implements StreamFactoryInterface
{
    public function createStream(string $content = ''): StreamInterface
    {
        $resource = fopen('php://temp', 'r+');
        $stream = new Stream(new StreamWrapper($resource));
        $stream->write($content);
        return $stream;
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $resource = fopen($filename, $mode);
        return new Stream(new StreamWrapper($resource));
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream(new StreamWrapper($resource));
    }
}
