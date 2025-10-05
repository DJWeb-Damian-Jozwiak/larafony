<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\ErrorHandler;

use Larafony\Framework\ErrorHandler\DetailedErrorHandler;
use PHPUnit\Framework\TestCase;

final class DetailedErrorHandlerTest extends TestCase
{
    public function testHandleOutputsHtmlWithExceptionClass(): void
    {
        $handler = new DetailedErrorHandler();
        $exception = new \Exception('Test exception message');

        ob_start();
        $handler->handle($exception);
        $output = ob_get_clean();

        $this->assertStringContainsString('Test exception message', $output);
        $this->assertStringContainsString('Exception', $output);
        $this->assertStringContainsString('💥', $output);
    }

    public function testHandleOutputsBacktrace(): void
    {
        $handler = new DetailedErrorHandler();
        $exception = new \RuntimeException('Runtime error');

        ob_start();
        $handler->handle($exception);
        $output = ob_get_clean();

        $this->assertStringContainsString('Backtrace', $output);
        $this->assertStringContainsString('RuntimeException', $output);
    }

    public function testRegisterCanBeCalled(): void
    {
        $handler = new DetailedErrorHandler();

        // Just verify register() runs without error
        $handler->register();

        $this->assertTrue(true);

        // Restore handlers
        restore_error_handler();
        restore_exception_handler();
    }

    public function testHandleSets500StatusCode(): void
    {
        $handler = new DetailedErrorHandler();
        $exception = new \Exception('Status test');

        ob_start();
        $handler->handle($exception);
        ob_end_clean();

        $this->assertSame(500, http_response_code());
    }
}
