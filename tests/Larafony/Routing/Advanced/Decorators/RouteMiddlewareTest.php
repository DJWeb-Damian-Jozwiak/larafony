<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced\Decorators;

use InvalidArgumentException;
use Larafony\Framework\Routing\Advanced\Decorators\RouteMiddleware;
use Larafony\Framework\Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteMiddlewareTest extends TestCase
{
    public function testWithMiddlewareBeforeAddsMiddleware(): void
    {
        $middleware = new RouteMiddleware();
        $middleware->withMiddlewareBefore(TestMiddleware::class);

        $this->assertContains(TestMiddleware::class, $middleware->middlewareBefore);
    }

    public function testWithMiddlewareAfterAddsMiddleware(): void
    {
        $middleware = new RouteMiddleware();
        $middleware->withMiddlewareAfter(TestMiddleware::class);

        $this->assertContains(TestMiddleware::class, $middleware->middlewareAfter);
    }

    public function testWithoutMiddlewareAddsToExclusionList(): void
    {
        $middleware = new RouteMiddleware();
        $middleware->withoutMiddleware(TestMiddleware::class);

        $this->assertContains(TestMiddleware::class, $middleware->withoutMiddleware);
    }

    public function testWithMiddlewareBeforeThrowsExceptionForInvalidMiddleware(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('InvalidClass must implement MiddlewareInterface');

        $middleware = new RouteMiddleware();
        $middleware->withMiddlewareBefore('InvalidClass');
    }

    public function testWithMiddlewareAfterThrowsExceptionForInvalidMiddleware(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('InvalidClass must implement MiddlewareInterface');

        $middleware = new RouteMiddleware();
        $middleware->withMiddlewareAfter('InvalidClass');
    }

    public function testWithoutMiddlewareThrowsExceptionForInvalidMiddleware(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('InvalidClass must implement MiddlewareInterface');

        $middleware = new RouteMiddleware();
        $middleware->withoutMiddleware('InvalidClass');
    }

    public function testChainableInterface(): void
    {
        $middleware = new RouteMiddleware();
        $result = $middleware
            ->withMiddlewareBefore(TestMiddleware::class)
            ->withMiddlewareAfter(TestMiddleware::class)
            ->withoutMiddleware(TestMiddleware::class);

        $this->assertInstanceOf(RouteMiddleware::class, $result);
    }

    public function testMultipleMiddlewareCanBeAdded(): void
    {
        $middleware = new RouteMiddleware();
        $middleware
            ->withMiddlewareBefore(TestMiddleware::class)
            ->withMiddlewareBefore(AnotherTestMiddleware::class);

        $this->assertCount(2, $middleware->middlewareBefore);
        $this->assertContains(TestMiddleware::class, $middleware->middlewareBefore);
        $this->assertContains(AnotherTestMiddleware::class, $middleware->middlewareBefore);
    }
}

class TestMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }
}

class AnotherTestMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }
}
