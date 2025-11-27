<?php

declare(strict_types=1);

namespace Larafony\ErrorHandler;

use Larafony\Framework\Console\Application;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\ErrorHandler\Renderers\ConsoleRenderer;
use Larafony\Framework\ErrorHandler\Renderers\Partials\ConsoleRendererFactory;
use Larafony\Framework\Tests\TestCase;

class ConsoleFactoryTest extends TestCase
{
    public function testCreateRenderer()
    {
        $container = Application::instance() ;
        $output = $this->createMock(OutputContract::class);
        $container->set(OutputContract::class, $output);
        $renderer = new ConsoleRendererFactory($container)->create();
        $this->assertInstanceOf(ConsoleRenderer::class, $renderer);
    }
}