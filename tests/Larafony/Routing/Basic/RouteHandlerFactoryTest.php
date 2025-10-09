<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Basic;

use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Routing\Basic\Handlers\ClosureRouteHandler;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use Larafony\Framework\Web\Application;
use Larafony\Framework\Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouteHandlerFactoryTest extends TestCase
{
    private RouteHandlerFactory $factory;
    private Application $container;
    private ServerRequestFactory $requestFactory;
    private ResponseFactory $responseFactory;
    private StreamFactory $streamFactory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->container = Application::instance();
        $this->factory = $this->container->get(RouteHandlerFactory::class);
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
        $this->streamFactory = new StreamFactory();
    }

    public function testCreateWithClosure(): void
    {
        $closure = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200)
            ->withBody($this->streamFactory->createStream('Closure Handler'));

        $handler = $this->factory->create($closure);

        $this->assertInstanceOf(ClosureRouteHandler::class, $handler);

        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $response = $handler->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Closure Handler', $response->getBody()->getContents());
    }

    public function testCreateWithArray(): void
    {
        $handler = $this->factory->create([\Larafony\Framework\Tests\Routing\Fixtures\TestController::class, 'handle']);

        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $response = $handler->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Test Controller Response', $response->getBody()->getContents());
    }

    public function testCreateWithString(): void
    {
        $handler = $this->factory->create(\Larafony\Framework\Tests\Routing\Fixtures\TestController::class . '@handle');

        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $response = $handler->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Test Controller Response', $response->getBody()->getContents());
    }
}
