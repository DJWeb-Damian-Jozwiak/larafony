<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\WebSockets\Engine;

use Larafony\Framework\WebSockets\Engine\FiberEngine;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(FiberEngine::class)]
final class FiberEngineTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $engine = new FiberEngine();

        $this->assertInstanceOf(FiberEngine::class, $engine);
    }

    public function testThrowsWhenRunWithoutListen(): void
    {
        $engine = new FiberEngine();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Server not listening');

        $engine->run();
    }

    public function testAcceptsConnectionHandler(): void
    {
        $engine = new FiberEngine();
        $called = false;

        $engine->onConnection(function () use (&$called): void {
            $called = true;
        });

        $this->assertFalse($called);
    }

    public function testAcceptsDataHandler(): void
    {
        $engine = new FiberEngine();

        $engine->onData(function (): void {
        });

        $this->assertInstanceOf(FiberEngine::class, $engine);
    }

    public function testAcceptsCloseHandler(): void
    {
        $engine = new FiberEngine();

        $engine->onClose(function (): void {
        });

        $this->assertInstanceOf(FiberEngine::class, $engine);
    }

    public function testAcceptsErrorHandler(): void
    {
        $engine = new FiberEngine();

        $engine->onError(function (): void {
        });

        $this->assertInstanceOf(FiberEngine::class, $engine);
    }

    public function testStopCanBeCalledBeforeRun(): void
    {
        $engine = new FiberEngine();

        $engine->stop();

        $this->assertInstanceOf(FiberEngine::class, $engine);
    }

    public function testListenThrowsOnInvalidPort(): void
    {
        $engine = new FiberEngine();

        $this->expectException(RuntimeException::class);

        $engine->listen('0.0.0.0', 80);
    }
}
