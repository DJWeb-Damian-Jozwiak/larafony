<?php

namespace Larafony\ErrorHandler;

use Larafony\Framework\Container\Container;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\ErrorHandler\DetailedErrorHandler;
use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class ServiceProviderTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testErrorServiceProvider()
    {
        $container = new Container();
        $serviceProvider = new ErrorHandlerServiceProvider();
        $serviceProvider->register($container);
        $serviceProvider->boot($container);
        $this->assertTrue($container->has(DetailedErrorHandler::class));
    }
}