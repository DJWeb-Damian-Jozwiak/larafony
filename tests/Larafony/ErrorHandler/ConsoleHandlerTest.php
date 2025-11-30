<?php

declare(strict_types=1);

namespace Larafony\ErrorHandler;

use Larafony\Framework\ErrorHandler\Handlers\ConsoleHandler;
use Larafony\Framework\ErrorHandler\Renderers\ConsoleRenderer;
use Larafony\Framework\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ConsoleHandlerTest extends TestCase
{
    private ConsoleRenderer|MockObject $renderer;
    private array $output;
    private ConsoleHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(ConsoleRenderer::class);
        $this->output = [];
        $this->handler = new ConsoleHandler(
            $this->renderer,
            fn(string $content) => $this->output[] = $content
        );
    }

    public function testHandleException(): void
    {
        $exception = new \Exception('Test exception');

        $this->handler->handleException($exception);

        $this->assertNotEmpty($this->output);
    }

    public function testHandleExceptionWithRenderingFailure(): void
    {
        $exception = new \Exception('Test exception');

        $this->handler->handleException($exception);
        $errors = ['Critical error occurred. Please check error logs.'];
        $this->assertEquals($errors, $this->output);
    }
}