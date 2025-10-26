<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Basic\Factories;

use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Routing\Basic\Factories\StringHandlerFactory;
use Larafony\Framework\Routing\Basic\Handlers\ClassMethodRouteHandler;
use Larafony\Framework\Routing\Basic\Handlers\FunctionRouteHandler;
use Larafony\Framework\Routing\Basic\Handlers\InvocableClassRouteHandler;
use Larafony\Framework\Web\Application;
use Larafony\Framework\Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Helper function for testing
function testStringHandlerFunction(ServerRequestInterface $request): ResponseInterface
{
    return (new ResponseFactory())->createResponse(200)
        ->withBody((new StreamFactory())->createStream('Function Handler'));
}

class StringHandlerFactoryTest extends TestCase
{
    private Application $container;
    private StringHandlerFactory $factory;
    private ServerRequestFactory $requestFactory;
    private ResponseFactory $responseFactory;
    private StreamFactory $streamFactory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->container = Application::instance();
        $this->factory = new StringHandlerFactory($this->container);
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
        $this->streamFactory = new StreamFactory();
    }

    public function testCreateWithClassMethodNotation(): void
    {
        $handler = $this->factory->create(\Larafony\Framework\Tests\Routing\Fixtures\TestController::class . '@handle');

        // Now returns FormRequestAwareHandler which supports both ServerRequest and FormRequest DTOs
        $this->assertInstanceOf(\Larafony\Framework\Validation\Handlers\FormRequestAwareHandler::class, $handler);

        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $response = $handler->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Test Controller Response', $response->getBody()->getContents());
    }

    public function testCreateWithInvocableClass(): void
    {
        $handler = $this->factory->create(\Larafony\Framework\Tests\Routing\Fixtures\InvocableController::class);

        $this->assertInstanceOf(InvocableClassRouteHandler::class, $handler);

        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $response = $handler->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Invocable Response', $response->getBody()->getContents());
    }

    public function testCreateWithFunctionName(): void
    {
        $handler = $this->factory->create(__NAMESPACE__ . '\\testStringHandlerFunction');

        $this->assertInstanceOf(FunctionRouteHandler::class, $handler);

        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $response = $handler->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Function Handler', $response->getBody()->getContents());
    }
}
