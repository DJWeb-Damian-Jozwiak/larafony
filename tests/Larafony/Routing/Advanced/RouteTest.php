<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced;

use Larafony\Framework\Http\Enums\HttpMethod;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Routing\Advanced\Decorators\RouteMiddleware;
use Larafony\Framework\Routing\Advanced\Route;
use Larafony\Framework\Routing\Advanced\RouteBinding;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use Larafony\Framework\Tests\Routing\Advanced\Decorators\TestMiddleware;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Web\Application;
use Psr\Http\Message\ServerRequestInterface;

class RouteTest extends TestCase
{
    private RouteHandlerFactory $factory;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $app = Application::instance();
        $this->factory = $app->get(RouteHandlerFactory::class);
        $this->responseFactory = new ResponseFactory();
    }

    public function testConstructorInitializesProperties(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);

        $route = new Route(
            '/users/<id>',
            HttpMethod::GET,
            $handler,
            $this->factory,
            'users.show'
        );

        $this->assertSame('/users/<id>', $route->path);
        $this->assertSame(HttpMethod::GET, $route->method);
        $this->assertSame('users.show', $route->name);
        $this->assertInstanceOf(RouteMiddleware::class, $route->getMiddleware());
        $this->assertEmpty($route->bindings);
        $this->assertEmpty($route->parameters);
    }

    public function testBindAddsBindingForValidParameter(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id>', HttpMethod::GET, $handler, $this->factory);

        $result = $route->bind('id', 'App\Models\User', 'findById');

        $this->assertSame($route, $result);
        $this->assertArrayHasKey('id', $route->bindings);
        $this->assertInstanceOf(RouteBinding::class, $route->bindings['id']);
        $this->assertSame('App\Models\User', $route->bindings['id']->modelClass);
        $this->assertSame('findById', $route->bindings['id']->findMethod);
    }

    public function testBindIgnoresNonExistentParameter(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);

        $result = $route->bind('id', 'App\Models\User');

        $this->assertSame($route, $result);
        $this->assertEmpty($route->bindings);
    }

    public function testWithParametersSetsParameters(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id>', HttpMethod::GET, $handler, $this->factory);

        $parameters = ['id' => '123', 'name' => 'John'];
        $result = $route->withParameters($parameters);

        $this->assertSame($route, $result);
        $this->assertSame($parameters, $route->parameters);
    }

    public function testWithMiddlewareBeforeAddsMiddleware(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);

        $result = $route->withMiddlewareBefore(TestMiddleware::class);

        $this->assertSame($route, $result);
        $this->assertContains(TestMiddleware::class, $route->getMiddleware()->middlewareBefore);
    }

    public function testWithMiddlewareAfterAddsMiddleware(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);

        $result = $route->withMiddlewareAfter(TestMiddleware::class);

        $this->assertSame($route, $result);
        $this->assertContains(TestMiddleware::class, $route->getMiddleware()->middlewareAfter);
    }

    public function testWithoutMiddlewareAddsToExclusionList(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);

        $result = $route->withoutMiddleware(TestMiddleware::class);

        $this->assertSame($route, $result);
        $this->assertContains(TestMiddleware::class, $route->getMiddleware()->withoutMiddleware);
    }

    public function testGetMiddlewareReturnsMiddlewareInstance(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);

        $middleware = $route->getMiddleware();

        $this->assertInstanceOf(RouteMiddleware::class, $middleware);
    }

    public function testChainableFluentInterface(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id>', HttpMethod::GET, $handler, $this->factory);

        $result = $route
            ->bind('id', 'App\Models\User')
            ->withParameters(['id' => '123'])
            ->withMiddlewareBefore(TestMiddleware::class)
            ->withMiddlewareAfter(TestMiddleware::class);

        $this->assertSame($route, $result);
    }
}
