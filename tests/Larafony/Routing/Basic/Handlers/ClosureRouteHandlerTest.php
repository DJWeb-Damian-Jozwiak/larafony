<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Basic\Handlers;

use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Routing\Basic\Handlers\ClosureRouteHandler;
use Larafony\Framework\Tests\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ClosureRouteHandlerTest extends TestCase
{
    private ServerRequestFactory $requestFactory;
    private ResponseFactory $responseFactory;
    private StreamFactory $streamFactory;

    protected function setUp(): void
    {
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
        $this->streamFactory = new StreamFactory();
    }

    public function testHandleInvokesClosure(): void
    {
        $closure = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200)
            ->withBody($this->streamFactory->createStream('Closure Response'));

        $handler = new ClosureRouteHandler($closure);
        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $response = $handler->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Closure Response', $response->getBody()->getContents());
    }

    public function testHandlePassesRequest(): void
    {
        $requestPassed = null;

        $closure = function (ServerRequestInterface $request) use (&$requestPassed) {
            $requestPassed = $request;
            return $this->responseFactory->createResponse(200);
        };

        $handler = new ClosureRouteHandler($closure);
        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $handler->handle($request);

        $this->assertSame($request, $requestPassed);
    }
}
