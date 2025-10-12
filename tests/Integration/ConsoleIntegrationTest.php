<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Integration;

use Larafony\Framework\Config\ServiceProviders\ConfigServiceProvider;
use Larafony\Framework\Console\Application;
use Larafony\Framework\Console\Formatters\Styles\DangerStyle;
use Larafony\Framework\Console\Formatters\Styles\InfoStyle;
use Larafony\Framework\Console\Formatters\Styles\SuccessStyle;
use Larafony\Framework\Console\Kernel;
use Larafony\Framework\Console\ServiceProviders\ConsoleServiceProvider;
use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;
use Larafony\Framework\Tests\TestCase;
use Psr\Http\Message\StreamInterface;

class ConsoleIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        restore_error_handler();
        restore_exception_handler();
        Application::empty();
    }

    public function testApplicationRespondsWith200(): void
    {
        $app = require __DIR__ . '/../../demo-app/bootstrap/console_app.php';
        $steam = $this->createMock(StreamInterface::class);$app->set('output_stream', $steam);
        $text = new SuccessStyle()->apply('Hello, John!').PHP_EOL;
        $steam->expects($this->once())->method('write')->with($text);
        $app->set('output_stream', $steam);
        $this->assertInstanceOf(Application::class, $app);
        $_SERVER['argv'] = ['bin/console.php', 'greet', 'John'];
        $app->handle();
        $_SERVER['argv'] = [];
    }

    public function testApplicationRespondsWith404(): void
    {
        $app = require __DIR__ . '/../../demo-app/bootstrap/console_app.php';
        $steam = $this->createMock(StreamInterface::class);$app->set('output_stream', $steam);
        $text = new DangerStyle()->apply('Command not found').PHP_EOL;
        $steam->expects($this->once())->method('write')->with($text);
        $app->set('output_stream', $steam);
        $this->assertInstanceOf(Application::class, $app);
        $_SERVER['argv'] = ['bin/console.php', 'commandNotExiting'];
        $app->handle();
        $_SERVER['argv'] = [];
    }
}