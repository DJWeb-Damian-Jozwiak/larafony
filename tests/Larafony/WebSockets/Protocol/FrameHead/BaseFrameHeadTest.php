<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\WebSockets\Protocol\FrameHead;

use Larafony\Framework\WebSockets\Protocol\Frame;
use Larafony\Framework\WebSockets\Protocol\FrameHead\BaseFrameHead;
use Larafony\Framework\WebSockets\Protocol\FrameHead\LargeFrameHead;
use Larafony\Framework\WebSockets\Protocol\FrameHead\MediumFrameHead;
use Larafony\Framework\WebSockets\Protocol\FrameHead\TinyFrameHead;
use PHPUnit\Framework\TestCase;

final class BaseFrameHeadTest extends TestCase
{
    public function testForReturnsTinyFrameHeadForSmallPayload(): void
    {
        $frame = Frame::text('Hello');

        $frameHead = BaseFrameHead::for($frame);

        $this->assertInstanceOf(TinyFrameHead::class, $frameHead);
    }

    public function testForReturnsTinyFrameHeadFor125Bytes(): void
    {
        $frame = Frame::text(str_repeat('x', 125));

        $frameHead = BaseFrameHead::for($frame);

        $this->assertInstanceOf(TinyFrameHead::class, $frameHead);
    }

    public function testForReturnsMediumFrameHeadFor126Bytes(): void
    {
        $frame = Frame::text(str_repeat('x', 126));

        $frameHead = BaseFrameHead::for($frame);

        $this->assertInstanceOf(MediumFrameHead::class, $frameHead);
    }

    public function testForReturnsMediumFrameHeadFor65535Bytes(): void
    {
        $frame = Frame::text(str_repeat('x', 65535));

        $frameHead = BaseFrameHead::for($frame);

        $this->assertInstanceOf(MediumFrameHead::class, $frameHead);
    }

    public function testForReturnsLargeFrameHeadFor65536Bytes(): void
    {
        $frame = Frame::text(str_repeat('x', 65536));

        $frameHead = BaseFrameHead::for($frame);

        $this->assertInstanceOf(LargeFrameHead::class, $frameHead);
    }

    public function testLengthPropertyHookReturnsPayloadLength(): void
    {
        $frame = Frame::text('Hello World');
        $frameHead = BaseFrameHead::for($frame);

        $this->assertSame(11, $frameHead->length);
    }

    public function testLengthPropertyHookForEmptyPayload(): void
    {
        $frame = Frame::text('');
        $frameHead = BaseFrameHead::for($frame);

        $this->assertSame(0, $frameHead->length);
    }
}
