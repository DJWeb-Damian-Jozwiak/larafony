<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Basic;

use Larafony\Framework\Http\Enums\HttpMethod;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Routing\Basic\Route;
use Larafony\Framework\Routing\Basic\RouteCollection;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use Larafony\Framework\Routing\Exceptions\RouteNotFoundError;
use Larafony\Framework\Web\Application;
use Larafony\Framework\Tests\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class RouteCollectionTest extends TestCase
{
    private RouteCollection $collection;
    private RouteHandlerFactory $factory;
    private ServerRequestFactory $requestFactory;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $app = Application::instance();
        $this->collection = $app->get(RouteCollection::class);
        $this->factory = $app->get(RouteHandlerFactory::class);
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
    }

    public function testAddRouteAddsToCollection(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/test', HttpMethod::GET, $handler, $this->factory);

        $this->collection->addRoute($route);

        $this->assertCount(1, $this->collection->routes);
        $this->assertSame($route, $this->collection->routes[0]);
    }

    public function testAddRouteMultipleRoutes(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route1 = new Route('/test1', HttpMethod::GET, $handler, $this->factory);
        $route2 = new Route('/test2', HttpMethod::POST, $handler, $this->factory);

        $this->collection->addRoute($route1);
        $this->collection->addRoute($route2);

        $this->assertCount(2, $this->collection->routes);
        $this->assertSame($route1, $this->collection->routes[0]);
        $this->assertSame($route2, $this->collection->routes[1]);
    }

    public function testFindRouteReturnsMatchingRoute(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/test', HttpMethod::GET, $handler, $this->factory);

        $this->collection->addRoute($route);

        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $foundRoute = $this->collection->findRoute($request);

        $this->assertSame($route, $foundRoute);
    }

    public function testFindRouteThrowsWhenNoMatch(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/test', HttpMethod::GET, $handler, $this->factory);

        $this->collection->addRoute($route);

        $request = $this->requestFactory->createServerRequest('GET', '/other');

        $this->expectException(RouteNotFoundError::class);
        $this->expectExceptionMessage('Route for GET /other not found');

        $this->collection->findRoute($request);
    }

    public function testFindRouteMatchesFirstRoute(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route1 = new Route('/test', HttpMethod::GET, $handler, $this->factory, 'first');
        $route2 = new Route('/test', HttpMethod::GET, $handler, $this->factory, 'second');

        $this->collection->addRoute($route1);
        $this->collection->addRoute($route2);

        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $foundRoute = $this->collection->findRoute($request);

        $this->assertSame('first', $foundRoute->name);
    }
}
