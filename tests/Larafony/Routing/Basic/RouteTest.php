<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Basic;

use Larafony\Framework\Http\Enums\HttpMethod;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Routing\Basic\Route;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use Larafony\Framework\Web\Application;
use Larafony\Framework\Tests\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class RouteTest extends TestCase
{
    private RouteHandlerFactory $factory;
    private ServerRequestFactory $requestFactory;
    private ResponseFactory $responseFactory;
    private StreamFactory $streamFactory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $app = Application::instance();
        $this->factory = $app->get(RouteHandlerFactory::class);
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
        $this->streamFactory = new StreamFactory();
    }

    public function testConstructorSetsProperties(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);

        $route = new Route(
            '/test',
            HttpMethod::GET,
            $handler,
            $this->factory,
            'test.route'
        );

        $this->assertSame('/test', $route->path);
        $this->assertSame(HttpMethod::GET, $route->method);
        $this->assertSame('test.route', $route->name);
    }

    public function testConstructorWithoutName(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);

        $route = new Route(
            '/test',
            HttpMethod::POST,
            $handler,
            $this->factory
        );

        $this->assertNull($route->name);
    }

    public function testHandleCallsHandler(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200)
            ->withBody($this->streamFactory->createStream('Handler Response'));

        $route = new Route(
            '/test',
            HttpMethod::GET,
            $handler,
            $this->factory
        );

        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $response = $route->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Handler Response', $response->getBody()->getContents());
    }
}
