<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced\Attributes;

use Larafony\Framework\Http\Enums\HttpMethod;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Routing\Advanced\Attributes\Middleware;
use Larafony\Framework\Routing\Advanced\Route;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use Larafony\Framework\Tests\Routing\Advanced\Decorators\TestMiddleware;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Web\Application;
use Psr\Http\Message\ServerRequestInterface;

class MiddlewareTest extends TestCase
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

    public function testConstructorWithBeforeGlobal(): void
    {
        $middleware = new Middleware(beforeGlobal: [TestMiddleware::class]);

        $reflection = new \ReflectionProperty($middleware, 'beforeGlobal');

        $this->assertSame([TestMiddleware::class], $reflection->getValue($middleware));
    }

    public function testConstructorWithAfterGlobal(): void
    {
        $middleware = new Middleware(afterGlobal: [TestMiddleware::class]);

        $reflection = new \ReflectionProperty($middleware, 'afterGlobal');

        $this->assertSame([TestMiddleware::class], $reflection->getValue($middleware));
    }

    public function testConstructorWithWithoutMiddleware(): void
    {
        $middleware = new Middleware(withoutMiddleware: [TestMiddleware::class]);

        $reflection = new \ReflectionProperty($middleware, 'withoutMiddleware');

        $this->assertSame([TestMiddleware::class], $reflection->getValue($middleware));
    }

    public function testAddToRouteAppliesBeforeMiddleware(): void
    {
        $middleware = new Middleware(beforeGlobal: [TestMiddleware::class]);
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/test', HttpMethod::GET, $handler, $this->factory);

        $middleware->addToRoute($route);

        $this->assertContains(TestMiddleware::class, $route->getMiddleware()->middlewareBefore);
    }

    public function testAddToRouteAppliesAfterMiddleware(): void
    {
        $middleware = new Middleware(afterGlobal: [TestMiddleware::class]);
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/test', HttpMethod::GET, $handler, $this->factory);

        $middleware->addToRoute($route);

        $this->assertContains(TestMiddleware::class, $route->getMiddleware()->middlewareAfter);
    }

    public function testAddToRouteAppliesWithoutMiddleware(): void
    {
        $middleware = new Middleware(withoutMiddleware: [TestMiddleware::class]);
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/test', HttpMethod::GET, $handler, $this->factory);

        $middleware->addToRoute($route);

        $this->assertContains(TestMiddleware::class, $route->getMiddleware()->withoutMiddleware);
    }

    public function testAttributeTargetsClassAndMethod(): void
    {
        $reflection = new \ReflectionClass(Middleware::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        $this->assertNotEmpty($attributes);
        $attribute = $attributes[0]->newInstance();
        $expectedFlags = \Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS;
        $this->assertSame($expectedFlags, $attribute->flags);
    }
}
