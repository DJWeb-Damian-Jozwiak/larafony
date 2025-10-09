<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Basic;

use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Routing\Basic\Router;
use Larafony\Framework\Routing\Exceptions\RouteNotFoundError;
use Larafony\Framework\Web\Application;
use Larafony\Framework\Tests\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class RouterTest extends TestCase
{
    private Router $router;
    private ServerRequestFactory $requestFactory;
    private ResponseFactory $responseFactory;
    private StreamFactory $streamFactory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $app = Application::instance();
        $this->router = $app->get(Router::class);
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
        $this->streamFactory = new StreamFactory();
    }

    public function testAddRouteByParamsWithClosure(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200)
            ->withBody($this->streamFactory->createStream('Test Response'));

        $result = $this->router->addRouteByParams('GET', '/test', $handler, 'test.route');

        $this->assertSame($this->router, $result);
        $this->assertCount(1, $this->router->routes->routes);
    }

    public function testHandleReturnsResponse(): void
    {
        $this->router->addRouteByParams(
            'GET',
            '/test',
            fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200)
                ->withBody($this->streamFactory->createStream('Handler Response'))
        );

        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $response = $this->router->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Handler Response', $response->getBody()->getContents());
    }

    public function testHandleThrowsWhenRouteNotFound(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/nonexistent');

        $this->expectException(RouteNotFoundError::class);
        $this->expectExceptionMessage('Route for GET /nonexistent not found');

        $this->router->handle($request);
    }

    public function testAddRouteByParamsSupportsMultipleMethods(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);

        $this->router->addRouteByParams('GET', '/test', $handler);
        $this->router->addRouteByParams('POST', '/test', $handler);
        $this->router->addRouteByParams('PUT', '/test', $handler);
        $this->router->addRouteByParams('DELETE', '/test', $handler);

        $this->assertCount(4, $this->router->routes->routes);
    }

    public function testAddRouteByParamsWithoutName(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);

        $this->router->addRouteByParams('GET', '/test', $handler);

        $this->assertCount(1, $this->router->routes->routes);
        $this->assertNull($this->router->routes->routes[0]->name);
    }

    public function testAddRouteByParamsConvertsMethodToUppercase(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200)
            ->withBody($this->streamFactory->createStream('Response'));

        $this->router->addRouteByParams('get', '/test', $handler);

        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $response = $this->router->handle($request);

        $this->assertSame(200, $response->getStatusCode());
    }
}
