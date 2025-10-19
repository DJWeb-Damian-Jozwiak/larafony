<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced\Compiled;

use Larafony\Framework\Http\Enums\HttpMethod;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Routing\Advanced\Compiled\CompiledRoute;
use Larafony\Framework\Routing\Advanced\Compiled\RouteCompiler;
use Larafony\Framework\Routing\Advanced\Route;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Web\Application;
use Psr\Http\Message\ServerRequestInterface;

class RouteCompilerTest extends TestCase
{
    private RouteCompiler $compiler;
    private RouteHandlerFactory $factory;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $app = Application::instance();
        $this->compiler = new RouteCompiler();
        $this->factory = $app->get(RouteHandlerFactory::class);
        $this->responseFactory = new ResponseFactory();
    }

    public function testCompileSimpleRoute(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);

        $compiled = $this->compiler->compile($route);

        $this->assertInstanceOf(CompiledRoute::class, $compiled);
        $this->assertEmpty($compiled->variables);
        $this->assertEmpty($compiled->patterns);
    }

    public function testCompileRouteWithParameter(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id>', HttpMethod::GET, $handler, $this->factory);

        $compiled = $this->compiler->compile($route);

        $this->assertSame(['id'], $compiled->variables);
        $this->assertArrayHasKey('id', $compiled->patterns);
        $this->assertSame('[\d\p{L}-]+', $compiled->patterns['id']);
    }

    public function testCompileRouteWithPatternedParameter(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id:\d+>', HttpMethod::GET, $handler, $this->factory);

        $compiled = $this->compiler->compile($route);

        $this->assertSame(['id'], $compiled->variables);
        $this->assertSame('\d+', $compiled->patterns['id']);
    }

    public function testCompileRouteWithMultipleParameters(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<userId>/posts/<postId:\d+>', HttpMethod::GET, $handler, $this->factory);

        $compiled = $this->compiler->compile($route);

        $this->assertSame(['userId', 'postId'], $compiled->variables);
        $this->assertSame('[\d\p{L}-]+', $compiled->patterns['userId']);
        $this->assertSame('\d+', $compiled->patterns['postId']);
    }

    public function testCompiledRegexMatchesCorrectly(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id:\d+>', HttpMethod::GET, $handler, $this->factory);

        $compiled = $this->compiler->compile($route);

        $this->assertSame(1, preg_match($compiled->regex, '/users/123'));
        $this->assertSame(0, preg_match($compiled->regex, '/users/abc'));
    }

    public function testCompiledRegexExtractsParameters(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id:\d+>/posts/<slug:[a-z-]+>', HttpMethod::GET, $handler, $this->factory);

        $compiled = $this->compiler->compile($route);

        preg_match($compiled->regex, '/users/123/posts/hello-world', $matches);

        $this->assertSame('123', $matches['id']);
        $this->assertSame('hello-world', $matches['slug']);
    }
}
