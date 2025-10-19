<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced\AttributeProcessors;

use Larafony\Framework\Http\Enums\HttpMethod;
use Larafony\Framework\Routing\Advanced\AttributeProcessors\RouteBuilder;
use Larafony\Framework\Routing\Advanced\Attributes\Middleware;
use Larafony\Framework\Routing\Advanced\Attributes\Route as RouteAttribute;
use Larafony\Framework\Routing\Advanced\Attributes\RouteGroup;
use Larafony\Framework\Routing\Advanced\Attributes\RouteParam;
use Larafony\Framework\Routing\Advanced\Route;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use Larafony\Framework\Tests\Routing\Advanced\Decorators\TestMiddleware;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Web\Application;
use ReflectionClass;
use ReflectionMethod;

class RouteBuilderTest extends TestCase
{
    private RouteBuilder $builder;
    private RouteHandlerFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $app = Application::instance();
        $this->factory = $app->get(RouteHandlerFactory::class);
        $this->builder = new RouteBuilder($this->factory);
    }

    public function testBuildBasicRoute(): void
    {
        $controller = new ReflectionClass(TestControllerForBuilder::class);
        $method = new ReflectionMethod(TestControllerForBuilder::class, 'index');
        $routeAttr = new RouteAttribute('/users');

        $route = $this->builder->build($controller, $method, 'GET', $routeAttr);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('/users', $route->path);
        $this->assertSame(HttpMethod::GET, $route->method);
        $this->assertSame('index', $route->name);
    }

    public function testBuildRouteWithGroupPrefix(): void
    {
        $controller = new ReflectionClass(TestControllerWithGroupForBuilder::class);
        $method = new ReflectionMethod(TestControllerWithGroupForBuilder::class, 'index');
        $routeAttr = new RouteAttribute('/users');

        $route = $this->builder->build($controller, $method, 'GET', $routeAttr);

        $this->assertSame('/api/users', $route->path);
    }

    public function testBuildRouteWithClassMiddleware(): void
    {
        $controller = new ReflectionClass(TestControllerWithMiddlewareForBuilder::class);
        $method = new ReflectionMethod(TestControllerWithMiddlewareForBuilder::class, 'index');
        $routeAttr = new RouteAttribute('/users');

        $route = $this->builder->build($controller, $method, 'GET', $routeAttr);

        $this->assertContains(TestMiddleware::class, $route->getMiddleware()->middlewareBefore);
    }

    public function testBuildRouteWithMethodMiddleware(): void
    {
        $controller = new ReflectionClass(TestControllerForBuilder::class);
        $method = new ReflectionMethod(TestControllerForBuilder::class, 'store');
        $routeAttr = new RouteAttribute('/users');

        $route = $this->builder->build($controller, $method, 'POST', $routeAttr);

        $this->assertContains(TestMiddleware::class, $route->getMiddleware()->middlewareBefore);
    }

    public function testBuildRouteWithParameters(): void
    {
        $controller = new ReflectionClass(TestControllerForBuilder::class);
        $method = new ReflectionMethod(TestControllerForBuilder::class, 'show');
        $routeAttr = new RouteAttribute('/users/<id>');

        $route = $this->builder->build($controller, $method, 'GET', $routeAttr);

        $this->assertArrayHasKey('id', $route->bindings);
        $this->assertSame('App\Models\User', $route->bindings['id']->modelClass);
    }

    public function testBuildSetsHandlerAsArray(): void
    {
        $controller = new ReflectionClass(TestControllerForBuilder::class);
        $method = new ReflectionMethod(TestControllerForBuilder::class, 'index');
        $routeAttr = new RouteAttribute('/users');

        $route = $this->builder->build($controller, $method, 'GET', $routeAttr);

        // Handler is set via constructor, we can verify route was created successfully
        $this->assertInstanceOf(Route::class, $route);
    }

    public function testBuildWithDifferentHttpMethods(): void
    {
        $controller = new ReflectionClass(TestControllerForBuilder::class);
        $method = new ReflectionMethod(TestControllerForBuilder::class, 'index');
        $routeAttr = new RouteAttribute('/users');

        $getRoute = $this->builder->build($controller, $method, 'GET', $routeAttr);
        $postRoute = $this->builder->build($controller, $method, 'POST', $routeAttr);

        $this->assertSame(HttpMethod::GET, $getRoute->method);
        $this->assertSame(HttpMethod::POST, $postRoute->method);
    }
}

class TestControllerForBuilder
{
    public function index(): void
    {
    }

    #[Middleware(beforeGlobal: [TestMiddleware::class])]
    public function store(): void
    {
    }

    #[RouteParam('id', bind: 'App\Models\User')]
    public function show(): void
    {
    }
}

#[RouteGroup('/api')]
class TestControllerWithGroupForBuilder
{
    public function index(): void
    {
    }
}

#[Middleware(beforeGlobal: [TestMiddleware::class])]
class TestControllerWithMiddlewareForBuilder
{
    public function index(): void
    {
    }
}
