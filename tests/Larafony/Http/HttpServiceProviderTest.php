<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http;

use Larafony\Framework\Container\Container;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class HttpServiceProviderTest extends TestCase
{
    public function testServiceProvider()
    {
        $container = new Container();
        $serviceProvider = new HttpServiceProvider();
        $serviceProvider->register($container);
        $serviceProvider->boot($container);
        $this->assertTrue($container->has(RequestFactoryInterface::class));
        $this->assertTrue($container->has(ResponseFactoryInterface::class));
        $this->assertTrue($container->has(ServerRequestFactoryInterface::class));
        $this->assertTrue($container->has(StreamFactoryInterface::class));
        $this->assertTrue($container->has(UploadedFileFactoryInterface::class));
        $this->assertTrue($container->has(UriFactoryInterface::class));
    }
}