<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Exceptions;

use Larafony\Framework\Core\Exceptions\NotFoundError;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Routing\Exceptions\RouteNotFoundError;
use Larafony\Framework\Tests\TestCase;

class RouteNotFoundErrorTest extends TestCase
{
    private ServerRequestFactory $requestFactory;

    protected function setUp(): void
    {
        $this->requestFactory = new ServerRequestFactory();
    }

    public function testExceptionMessage(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/test/path');

        $exception = new RouteNotFoundError($request);

        $this->assertSame('Route for GET /test/path not found', $exception->getMessage());
    }

    public function testExceptionWithDifferentMethod(): void
    {
        $request = $this->requestFactory->createServerRequest('POST', '/api/users');

        $exception = new RouteNotFoundError($request);

        $this->assertSame('Route for POST /api/users not found', $exception->getMessage());
    }

    public function testExceptionExtendsNotFoundError(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/test');

        $exception = new RouteNotFoundError($request);

        $this->assertInstanceOf(NotFoundError::class, $exception);
    }
}
