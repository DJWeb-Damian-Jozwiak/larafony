<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\ServiceProviders;

use Larafony\Framework\Routing\Basic\Factories\ArrayHandlerFactory;
use Larafony\Framework\Routing\Basic\Factories\StringHandlerFactory;
use Larafony\Framework\Routing\Basic\Router;
use Larafony\Framework\Routing\ServiceProviders\RouteServiceProvider;
use Larafony\Framework\Tests\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class RouteServiceProviderTest extends TestCase
{
    public function testProvidersProperty(): void
    {
        $provider = new RouteServiceProvider();

        $providers = $provider->providers();

        $this->assertIsArray($providers);
        $this->assertArrayHasKey(RequestHandlerInterface::class, $providers);
        $this->assertSame(Router::class, $providers[RequestHandlerInterface::class]);
    }

    public function testArrayHandlerFactoryBinding(): void
    {
        $provider = new RouteServiceProvider();

        $providers = $provider->providers();

        $this->assertArrayHasKey(ArrayHandlerFactory::class, $providers);
        $this->assertSame(ArrayHandlerFactory::class, $providers[ArrayHandlerFactory::class]);
    }

    public function testStringHandlerFactoryBinding(): void
    {
        $provider = new RouteServiceProvider();

        $providers = $provider->providers();

        $this->assertArrayHasKey(StringHandlerFactory::class, $providers);
        $this->assertSame(StringHandlerFactory::class, $providers[StringHandlerFactory::class]);
    }
}
