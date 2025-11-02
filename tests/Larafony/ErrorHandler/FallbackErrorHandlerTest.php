<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\ErrorHandler;

use Larafony\Framework\ErrorHandler\FallbackErrorHandler;
use Larafony\Framework\View\Contracts\RendererContract;
use Larafony\Framework\View\ViewManager;
use PHPUnit\Framework\TestCase;

final class FallbackErrorHandlerTest extends TestCase
{
    public function testHandleCallsParentHandleSuccessfully(): void
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
        $handler = new FallbackErrorHandler($viewManager);
        $exception = new \Exception('Test exception');

        ob_start();
        $handler->handle($exception);
        $output = ob_get_clean();

        $this->assertStringContainsString('500', $output);
    }

    public function testHandleRendersFallbackWhenParentThrows(): void
    {
        $contract = $this->createMock(RendererContract::class);
        $contract->expects($this->once())
            ->method('render')
            ->willThrowException(new \RuntimeException('View rendering failed'));

        $viewManager = new ViewManager($contract);
        $handler = new FallbackErrorHandler($viewManager);
        $exception = new \Exception('Original exception');

        ob_start();
        $handler->handle($exception);
        $output = ob_get_clean();

        $this->assertStringContainsString('Server Error', $output);
        $this->assertStringContainsString('An error occurred while processing your request', $output);
    }

    public function testHandleRendersFallbackInDebugMode(): void
    {
        $contract = $this->createMock(RendererContract::class);
        $contract->expects($this->once())
            ->method('render')
            ->willThrowException(new \RuntimeException('View rendering failed'));

        $viewManager = new ViewManager($contract);
        $handler = new FallbackErrorHandler($viewManager, debug: true);
        $exception = new \Exception('Original exception');

        ob_start();
        $handler->handle($exception);
        $output = ob_get_clean();

        $this->assertStringContainsString('Original Error:', $output);
        $this->assertStringContainsString('Render Error:', $output);
        $this->assertStringContainsString('Original exception', $output);
        $this->assertStringContainsString('View rendering failed', $output);
    }

    public function testRegisterCanBeCalled(): void
    {
        $contract = $this->createMock(RendererContract::class);
        $viewManager = new ViewManager($contract);
        $handler = new FallbackErrorHandler($viewManager);

        $handler->register();

        $this->assertTrue(true);

        restore_error_handler();
        restore_exception_handler();
    }
}
