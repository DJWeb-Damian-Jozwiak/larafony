<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\ErrorHandler;

use ErrorException;
use Larafony\Framework\ErrorHandler\DetailedErrorHandler;
use Larafony\Framework\View\Contracts\RendererContract;
use Larafony\Framework\View\ViewManager;
use PHPUnit\Framework\TestCase;

final class DetailedErrorHandlerTest extends TestCase
{
    public function testHandleOutputsHtmlWithExceptionClass(): void
    {
        $contract = $this->createMock(RendererContract::class);
        $contract->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('errors.500'),
                $this->equalTo([])
            )
            ->willReturn('<h1>500</h1>');

        $viewManager = new ViewManager($contract);
        $handler = new DetailedErrorHandler($viewManager);
        $exception = new \Exception('Test exception message');

        ob_start();
        $handler->handle($exception);
        $output = ob_get_clean();

        $this->assertStringContainsString('500', $output);
    }

    public function testHandleOutputsBacktrace(): void
    {
        $contract = $this->createMock(RendererContract::class);
        $contract->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('errors.500'),
                $this->equalTo([])
            )
            ->willReturn('<h1>500 Error</h1>');

        $viewManager = new ViewManager($contract);
        $handler = new DetailedErrorHandler($viewManager);
        $exception = new \RuntimeException('Runtime error');

        ob_start();
        $handler->handle($exception);
        $output = ob_get_clean();

        $this->assertStringContainsString('500', $output);
    }

    public function testRegisterCanBeCalled(): void
    {
        $contract = $this->createStub(RendererContract::class);
        $viewManager = new ViewManager($contract);
        $handler = new DetailedErrorHandler($viewManager);

        // Just verify register() runs without error
        $handler->register();

        $this->assertTrue(true);

        // Restore handlers
        restore_error_handler();
        restore_exception_handler();
    }

    public function testHandleSets500StatusCode(): void
    {
        $contract = $this->createMock(RendererContract::class);
        $contract->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('errors.500'),
                $this->equalTo([])
            )
            ->willReturn('<h1>500</h1>');

        $viewManager = new ViewManager($contract);
        $handler = new DetailedErrorHandler($viewManager);
        $exception = new \Exception('Status test');

        ob_start();
        $handler->handle($exception);
        ob_end_clean();

        $this->assertSame(500, http_response_code());
    }

    public function testHandleInDebugModeRendersDebugView(): void
    {
        $contract = $this->createMock(RendererContract::class);
        $contract->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('errors.debug'),
                $this->callback(function ($data) {
                    return isset($data['exception'])
                        && isset($data['backtrace'])
                        && $data['exception']['message'] === 'Test exception message'
                        && $data['exception']['class'] === 'Exception';
                })
            )
            ->willReturn('<h1>Debug: Exception - Test exception message</h1>');

        $viewManager = new ViewManager($contract);
        $handler = new DetailedErrorHandler($viewManager, debug: true);
        $exception = new \Exception('Test exception message');

        ob_start();
        $handler->handle($exception);
        $output = ob_get_clean();

        $this->assertStringContainsString('Test exception message', $output);
    }

    public function testHandleInDebugModeIncludesBacktraceData(): void
    {
        $contract = $this->createMock(RendererContract::class);
        $contract->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('errors.debug'),
                $this->callback(function ($data) {
                    return isset($data['backtrace'])
                        && is_array($data['backtrace'])
                        && isset($data['exception']['class'])
                        && $data['exception']['class'] === 'RuntimeException';
                })
            )
            ->willReturn('<h1>Debug with backtrace</h1>');

        $viewManager = new ViewManager($contract);
        $handler = new DetailedErrorHandler($viewManager, debug: true);
        $exception = new \RuntimeException('Runtime error');

        ob_start();
        $handler->handle($exception);
        $output = ob_get_clean();

        $this->assertStringContainsString('backtrace', $output);
    }
}
