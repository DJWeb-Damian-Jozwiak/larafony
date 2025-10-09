<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Basic\Handlers;

use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Routing\Basic\Handlers\FunctionRouteHandler;
use Larafony\Framework\Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Helper function for testing
function testRouteHandlerFunction(ServerRequestInterface $request): ResponseInterface
{
    return (new ResponseFactory())->createResponse(200)
        ->withBody((new StreamFactory())->createStream('Function Response'));
}

class FunctionRouteHandlerTest extends TestCase
{
    private ServerRequestFactory $requestFactory;

    protected function setUp(): void
    {
        $this->requestFactory = new ServerRequestFactory();
    }

    public function testHandleCallsFunction(): void
    {
        $handler = new FunctionRouteHandler(__NAMESPACE__ . '\\testRouteHandlerFunction');
        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $response = $handler->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Function Response', $response->getBody()->getContents());
    }

    public function testConstructorThrowsWhenFunctionDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Function nonexistent_function does not exist');

        new FunctionRouteHandler('nonexistent_function');
    }
}
