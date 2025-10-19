<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced;

use Larafony\Framework\Http\Enums\HttpMethod;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Routing\Advanced\Route;
use Larafony\Framework\Routing\Advanced\RouteGroup;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use Larafony\Framework\Tests\Routing\Advanced\Decorators\TestMiddleware;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Web\Application;
use Psr\Http\Message\ServerRequestInterface;

class RouteGroupTest extends TestCase
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

    public function testConstructorNormalizesPrefix(): void
    {
        $group = new RouteGroup('api');

        $this->assertSame('/api', $group->prefix);
    }

    public function testConstructorWithLeadingSlash(): void
    {
        $group = new RouteGroup('/api');

        $this->assertSame('/api', $group->prefix);
    }

    public function testConstructorWithTrailingSlash(): void
    {
        $group = new RouteGroup('/api/');

        $this->assertSame('/api', $group->prefix);
    }

    public function testConstructorWithMultipleSlashes(): void
    {
        $group = new RouteGroup('///api///v1///');

        $this->assertSame('/api/v1', $group->prefix);
    }

    public function testConstructorWithEmptyPrefix(): void
    {
        $group = new RouteGroup('');

        $this->assertSame('/', $group->prefix);
    }

    public function testConstructorStoresMiddleware(): void
    {
        $before = [TestMiddleware::class];
        $after = [TestMiddleware::class];

        $group = new RouteGroup('/api', $before, $after);

        $this->assertSame($before, $group->middlewareBefore);
        $this->assertSame($after, $group->middlewareAfter);
    }

    public function testAddRoutePrefixesPath(): void
    {
        $group = new RouteGroup('/api');
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);

        $group->addRoute($route);

        $this->assertSame('/api/users', $route->path);
        $this->assertContains($route, $group->routes);
    }

    public function testAddRouteAppliesMiddlewareBefore(): void
    {
        $group = new RouteGroup('/api', [TestMiddleware::class]);
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);

        $group->addRoute($route);

        $this->assertContains(TestMiddleware::class, $route->getMiddleware()->middlewareBefore);
    }

    public function testAddRouteAppliesMiddlewareAfter(): void
    {
        $group = new RouteGroup('/api', [], [TestMiddleware::class]);
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);

        $group->addRoute($route);

        $this->assertContains(TestMiddleware::class, $route->getMiddleware()->middlewareAfter);
    }

    public function testNestedGroupCombinesPrefixes(): void
    {
        $parentGroup = new RouteGroup('/api');

        $parentGroup->group('/v1', function (RouteGroup $group) {
            $this->assertSame('/api/v1', $group->prefix);
        });
    }

    public function testNestedGroupInheritsMiddleware(): void
    {
        $parentGroup = new RouteGroup('/api', [TestMiddleware::class]);

        $parentGroup->group('/v1', function (RouteGroup $group) {
            $this->assertContains(TestMiddleware::class, $group->middlewareBefore);
        });
    }

    public function testNestedGroupAddsOwnMiddleware(): void
    {
        $parentGroup = new RouteGroup('/api', [TestMiddleware::class]);

        $parentGroup->group('/v1', function (RouteGroup $group) {
            // Parent middleware is inherited plus new middleware added
            $this->assertCount(2, $group->middlewareBefore);
        }, [TestMiddleware::class]);
    }

    public function testNestedGroupRoutesAreAddedToParent(): void
    {
        $parentGroup = new RouteGroup('/api');
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);

        $parentGroup->group('/v1', function (RouteGroup $group) use ($handler) {
            $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);
            $group->addRoute($route);
        });

        $this->assertCount(1, $parentGroup->routes);
        $this->assertSame('/api/v1/users', $parentGroup->routes[0]->path);
    }

    public function testGroupNormalizesNestedPrefix(): void
    {
        $parentGroup = new RouteGroup('/api/');

        $parentGroup->group('//v1//', function (RouteGroup $group) {
            $this->assertSame('/api/v1', $group->prefix);
        });
    }
}
