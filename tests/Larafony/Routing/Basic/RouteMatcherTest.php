<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Basic;

use Larafony\Framework\Http\Enums\HttpMethod;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Routing\Basic\Route;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use Larafony\Framework\Routing\Basic\RouteMatcher;
use Larafony\Framework\Web\Application;
use Larafony\Framework\Tests\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class RouteMatcherTest extends TestCase
{
    private RouteMatcher $matcher;
    private RouteHandlerFactory $factory;
    private ServerRequestFactory $requestFactory;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $app = Application::instance();
        $this->matcher = new RouteMatcher();
        $this->factory = $app->get(RouteHandlerFactory::class);
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
    }

    public function testMatchesWithExactPath(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/test', HttpMethod::GET, $handler, $this->factory);
        $request = $this->requestFactory->createServerRequest('GET', '/test');

        $this->assertTrue($this->matcher->matches($request, $route));
    }

    public function testMatchesWithTrailingSlashInRoute(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/test/', HttpMethod::GET, $handler, $this->factory);
        $request = $this->requestFactory->createServerRequest('GET', '/test');

        $this->assertTrue($this->matcher->matches($request, $route));
    }

    public function testMatchesWithTrailingSlashInRequest(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/test', HttpMethod::GET, $handler, $this->factory);
        $request = $this->requestFactory->createServerRequest('GET', '/test/');

        $this->assertTrue($this->matcher->matches($request, $route));
    }

    public function testMatchesWithBothTrailingSlashes(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/test/', HttpMethod::GET, $handler, $this->factory);
        $request = $this->requestFactory->createServerRequest('GET', '/test/');

        $this->assertTrue($this->matcher->matches($request, $route));
    }

    public function testDoesNotMatchDifferentPath(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/test', HttpMethod::GET, $handler, $this->factory);
        $request = $this->requestFactory->createServerRequest('GET', '/other');

        $this->assertFalse($this->matcher->matches($request, $route));
    }

    public function testDoesNotMatchDifferentMethod(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/test', HttpMethod::GET, $handler, $this->factory);
        $request = $this->requestFactory->createServerRequest('POST', '/test');

        $this->assertFalse($this->matcher->matches($request, $route));
    }

    public function testMatchesWithDifferentMethods(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);

        $getRoute = new Route('/test', HttpMethod::GET, $handler, $this->factory);
        $postRoute = new Route('/test', HttpMethod::POST, $handler, $this->factory);
        $putRoute = new Route('/test', HttpMethod::PUT, $handler, $this->factory);

        $getRequest = $this->requestFactory->createServerRequest('GET', '/test');
        $postRequest = $this->requestFactory->createServerRequest('POST', '/test');
        $putRequest = $this->requestFactory->createServerRequest('PUT', '/test');

        $this->assertTrue($this->matcher->matches($getRequest, $getRoute));
        $this->assertTrue($this->matcher->matches($postRequest, $postRoute));
        $this->assertTrue($this->matcher->matches($putRequest, $putRoute));

        $this->assertFalse($this->matcher->matches($getRequest, $postRoute));
        $this->assertFalse($this->matcher->matches($postRequest, $putRoute));
    }

    public function testMatchesWithCaseInsensitiveMethod(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/test', HttpMethod::GET, $handler, $this->factory);
        $request = $this->requestFactory->createServerRequest('get', '/test');

        $this->assertTrue($this->matcher->matches($request, $route));
    }
}
