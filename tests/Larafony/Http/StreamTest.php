<?php

namespace Tests\Http;

use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Http\Helpers\Stream\StreamMetaData;
use Larafony\Framework\Http\Stream;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    public function testToString()
    {
        $stream = new StreamFactory()->createStream();
        $stream->write('Hello, World!');
        $stream->rewind();
        $this->assertEquals('Hello, World!', (string)$stream);
    }

    public function testBrokenStream()
    {
        $object = new StreamFactory()->createStreamFromResource(fopen('php://memory', 'w'));

        $result = (string)$object;

        $this->assertEquals('', $result);
    }

    public function testClose()
    {
        $stream = new StreamFactory()->createStream();
        $stream->close();
        $this->assertFalse(is_resource($stream->detach()));
    }

     public function testDetach()
    {
        $stream = new StreamFactory()->createStream();
        $resource = $stream->detach();
        $this->assertIsResource($resource);
        $this->assertNull($stream->detach());
    }

    public function testGetSize()
    {
        $stream = new StreamFactory()->createStream();
        $stream->write('Hello, World!');
        $this->assertEquals(13, $stream->getSize());
    }

    public function testTell()
    {
        $stream = new StreamFactory()->createStream();
        $stream->write('Hello, World!');
        $this->assertEquals(13, $stream->tell());
    }

    public function testEof()
    {
        $stream = new StreamFactory()->createStream();
        $stream->write('Hello, World!');
        $stream->read(13);
        $this->assertTrue($stream->eof());
    }

    public function testSeek()
    {
        $stream = new StreamFactory()->createStream();
        $stream->seek(0);
        $this->assertEquals('', (string)$stream);
    }

    public function testIsSeekable()
    {
        $stream = new StreamFactory()->createStream();
        $this->assertTrue($stream->isSeekable());
    }

    public function testIsWritable()
    {
        $stream = new StreamFactory()->createStream();
        $this->assertTrue($stream->isWritable());
    }

    public function testWrite()
    {
        $stream = new StreamFactory()->createStream();
        $this->assertEquals(13, $stream->write('Hello, World!'));
    }

    public function testIsReadable()
    {
        $stream = new StreamFactory()->createStream();
        $this->assertTrue($stream->isReadable());
    }

    public function testRead()
    {
        $stream = new StreamFactory()->createStream();
        $stream->write('Hello, World!');
        $stream->rewind();
        $this->assertEquals('Hello', $stream->read(5));
        $stream->close();
        unset($stream);
        gc_collect_cycles();
    }

    public function testReadInvalidOffset()
    {
        $stream = new StreamFactory()->createStream();
        $stream->write('Hello, World!');
        $stream->rewind();
        $this->expectException(\InvalidArgumentException::class);
        $stream->read(0);
    }

    public function testGetContents()
    {
        $stream = new StreamFactory()->createStream();
        $stream->write('Hello, World!');
        $stream->rewind();
        $this->assertEquals('Hello, World!', $stream->getContents());
    }

    public function testGetMetadata()
    {
        $stream = new StreamFactory()->createStream();
        $meta = $stream->getMetadata();
        $this->assertInstanceOf(StreamMetaData::class, $meta);
        $blocked = $stream->getMetadata('blocked');
        $this->assertIsBool($blocked);
        $all = $stream->getMetadata()->toArray();
        $this->assertIsArray($all);
    }

}