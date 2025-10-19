<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced;

use Larafony\Framework\Http\Enums\HttpMethod;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Routing\Advanced\Route;
use Larafony\Framework\Routing\Advanced\RouteMatcher;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Web\Application;
use Psr\Http\Message\ServerRequestInterface;

class RouteMatcherTest extends TestCase
{
    private RouteMatcher $matcher;
    private RouteHandlerFactory $factory;
    private ResponseFactory $responseFactory;
    private ServerRequestFactory $requestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $app = Application::instance();
        $this->factory = $app->get(RouteHandlerFactory::class);
        $this->matcher = new RouteMatcher();
        $this->responseFactory = new ResponseFactory();
        $this->requestFactory = new ServerRequestFactory();
    }

    public function testMatchesSimpleParameter(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id>', HttpMethod::GET, $handler, $this->factory);
        $request = $this->requestFactory->createServerRequest('GET', '/users/123');

        $matches = $this->matcher->matches($request, $route);

        $this->assertTrue($matches);
        $this->assertSame('123', $route->parameters['id']);
    }

    public function testMatchesParameterWithPattern(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id:\d+>', HttpMethod::GET, $handler, $this->factory);
        $request = $this->requestFactory->createServerRequest('GET', '/users/123');

        $matches = $this->matcher->matches($request, $route);

        $this->assertTrue($matches);
        $this->assertSame('123', $route->parameters['id']);
    }

    public function testDoesNotMatchWhenPatternFails(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id:\d+>', HttpMethod::GET, $handler, $this->factory);
        $request = $this->requestFactory->createServerRequest('GET', '/users/abc');

        $matches = $this->matcher->matches($request, $route);

        $this->assertFalse($matches);
    }

    public function testMatchesMultipleParameters(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/posts/<category>/<slug>', HttpMethod::GET, $handler, $this->factory);
        $request = $this->requestFactory->createServerRequest('GET', '/posts/tech/my-post');

        $matches = $this->matcher->matches($request, $route);

        $this->assertTrue($matches);
        $this->assertSame('tech', $route->parameters['category']);
        $this->assertSame('my-post', $route->parameters['slug']);
    }

    public function testMatchesComplexPattern(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/posts/<category:[a-z]+>/<slug:[a-z-]+>', HttpMethod::GET, $handler, $this->factory);
        $request = $this->requestFactory->createServerRequest('GET', '/posts/technology/my-awesome-post');

        $matches = $this->matcher->matches($request, $route);

        $this->assertTrue($matches);
        $this->assertSame('technology', $route->parameters['category']);
        $this->assertSame('my-awesome-post', $route->parameters['slug']);
    }

    public function testNormalizesPathWithTrailingSlash(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id>', HttpMethod::GET, $handler, $this->factory);
        $request = $this->requestFactory->createServerRequest('GET', '/users/123/');

        $matches = $this->matcher->matches($request, $route);

        $this->assertTrue($matches);
    }

    public function testNormalizesPathWithMultipleSlashes(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id>', HttpMethod::GET, $handler, $this->factory);
        $request = $this->requestFactory->createServerRequest('GET', '///users///123');

        $matches = $this->matcher->matches($request, $route);

        $this->assertTrue($matches);
    }

    public function testDoesNotMatchDifferentPath(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id>', HttpMethod::GET, $handler, $this->factory);
        $request = $this->requestFactory->createServerRequest('GET', '/posts/123');

        $matches = $this->matcher->matches($request, $route);

        $this->assertFalse($matches);
    }

    public function testMatchesRootPath(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/', HttpMethod::GET, $handler, $this->factory);
        $request = $this->requestFactory->createServerRequest('GET', '/');

        $matches = $this->matcher->matches($request, $route);

        $this->assertTrue($matches);
    }

    public function testMatchesPathWithoutParameters(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);
        $request = $this->requestFactory->createServerRequest('GET', '/users');

        $matches = $this->matcher->matches($request, $route);

        $this->assertTrue($matches);
    }

    public function testDoesNotMatchDifferentMethod(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);
        $request = $this->requestFactory->createServerRequest('POST', '/users');

        $matches = $this->matcher->matches($request, $route);

        $this->assertFalse($matches);
    }
}
