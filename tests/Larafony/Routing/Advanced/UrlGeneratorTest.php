<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced;

use Larafony\Framework\Http\Enums\HttpMethod;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Routing\Advanced\Route;
use Larafony\Framework\Routing\Advanced\UrlGenerator;
use Larafony\Framework\Routing\Basic\RouteCollection;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Web\Application;
use Psr\Http\Message\ServerRequestInterface;

class UrlGeneratorTest extends TestCase
{
    private UrlGenerator $generator;
    private RouteCollection $routes;
    private RouteHandlerFactory $factory;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $app = Application::instance();
        $this->factory = $app->get(RouteHandlerFactory::class);
        $this->routes = new RouteCollection($app);
        $this->generator = new UrlGenerator($this->routes, 'https://example.com');
        $this->responseFactory = new ResponseFactory();
    }

    public function testGenerateSimpleRoute(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users', HttpMethod::GET, $handler, $this->factory, 'users.index');
        $this->routes->addRoute($route);

        $url = $this->generator->route('users.index');

        $this->assertSame('/users', $url);
    }

    public function testGenerateRouteWithParameters(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id>', HttpMethod::GET, $handler, $this->factory, 'users.show');
        $this->routes->addRoute($route);

        $url = $this->generator->route('users.show', ['id' => 123]);

        $this->assertSame('/users/123', $url);
    }

    public function testGenerateRouteWithMultipleParameters(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<userId>/posts/<postId>', HttpMethod::GET, $handler, $this->factory, 'posts.show');
        $this->routes->addRoute($route);

        $url = $this->generator->route('posts.show', ['userId' => 1, 'postId' => 42]);

        $this->assertSame('/users/1/posts/42', $url);
    }

    public function testGenerateRouteWithPatternedParameter(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/posts/<slug:[a-z-]+>', HttpMethod::GET, $handler, $this->factory, 'posts.slug');
        $this->routes->addRoute($route);

        $url = $this->generator->route('posts.slug', ['slug' => 'hello-world']);

        $this->assertSame('/posts/hello-world', $url);
    }

    public function testGenerateAbsoluteUrl(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id>', HttpMethod::GET, $handler, $this->factory, 'users.show');
        $this->routes->addRoute($route);

        $url = $this->generator->route('users.show', ['id' => 123], true);

        $this->assertSame('https://example.com/users/123', $url);
    }

    public function testRouteAbsoluteHelper(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users', HttpMethod::GET, $handler, $this->factory, 'users.index');
        $this->routes->addRoute($route);

        $url = $this->generator->routeAbsolute('users.index');

        $this->assertSame('https://example.com/users', $url);
    }

    public function testGenerateRouteWithExtraParameters(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id>', HttpMethod::GET, $handler, $this->factory, 'users.show');
        $this->routes->addRoute($route);

        $url = $this->generator->route('users.show', ['id' => 123, 'tab' => 'profile', 'page' => 2]);

        $this->assertSame('/users/123?tab=profile&page=2', $url);
    }

    public function testThrowsExceptionForMissingRoute(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Route for nonexistent not found');

        $this->generator->route('nonexistent');
    }

    public function testThrowsExceptionForMissingParameter(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id>', HttpMethod::GET, $handler, $this->factory, 'users.show');
        $this->routes->addRoute($route);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing required parameter 'id' for route");

        $this->generator->route('users.show');
    }
}
