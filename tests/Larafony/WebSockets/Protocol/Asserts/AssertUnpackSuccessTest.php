<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\WebSockets\Protocol\Asserts;

use Larafony\Framework\WebSockets\Protocol\Asserts\AssertUnpackSuccess;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AssertUnpackSuccessTest extends TestCase
{
    public function testReturnsArrayOnSuccess(): void
    {
        $input = [1 => 129, 2 => 5];
        $result = AssertUnpackSuccess::assert($input);

        $this->assertSame($input, $result);
    }

    public function testReturnsEmptyArrayOnEmptyInput(): void
    {
        $result = AssertUnpackSuccess::assert([]);

        $this->assertSame([], $result);
    }

    public function testThrowsOnFalse(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to unpack WebSocket frame data');

        AssertUnpackSuccess::assert(false);
    }
}
