<?php

namespace Larafony\ErrorHandler;

use Larafony\Framework\Config\ServiceProviders\ConfigServiceProvider;
use Larafony\Framework\Container\Container;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\ErrorHandler\DetailedErrorHandler;
use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\View\ServiceProviders\ViewServiceProvider;
use Larafony\Framework\Web\Application;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;


class ServiceProviderTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testErrorServiceProvider()
    {
        $this->markTestSkipped('add after integration tests with directory structure');
        $basePath = dirname(__DIR__, 2) . '/public';
        $app = Application::instance($basePath);
        $app->withServiceProviders([
            ConfigServiceProvider::class,
            ViewServiceProvider::class, ErrorHandlerServiceProvider::class,
            //add more providers as needed
        ]);
        $this->assertTrue($app->has(DetailedErrorHandler::class));
        /**
         * $app->run();
         * //test views etc
         */
    }
}