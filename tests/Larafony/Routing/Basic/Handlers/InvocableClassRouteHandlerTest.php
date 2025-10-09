<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Basic\Handlers;

use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Routing\Basic\Handlers\InvocableClassRouteHandler;
use Larafony\Framework\Web\Application;
use Larafony\Framework\Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class InvocableClassRouteHandlerTest extends TestCase
{
    private Application $container;
    private ServerRequestFactory $requestFactory;
    private ResponseFactory $responseFactory;
    private StreamFactory $streamFactory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->container = Application::instance();
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
        $this->streamFactory = new StreamFactory();
    }

    public function testHandleInvokesClass(): void
    {
        $controller = new class($this->responseFactory, $this->streamFactory) {
            public function __construct(
                private ResponseFactory $responseFactory,
                private StreamFactory $streamFactory
            ) {
            }

            public function __invoke(ServerRequestInterface $request): ResponseInterface
            {
                return $this->responseFactory->createResponse(200)
                    ->withBody($this->streamFactory->createStream('Invocable Response'));
            }
        };

        $this->container->set($controller::class, $controller);

        $handler = new InvocableClassRouteHandler($controller::class, $this->container);
        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $response = $handler->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Invocable Response', $response->getBody()->getContents());
    }

    public function testConstructorThrowsWhenClassDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class NonExistentClass does not exist');

        new InvocableClassRouteHandler('NonExistentClass', $this->container);
    }

    public function testConstructorThrowsWhenClassNotInvocable(): void
    {
        $controller = new class {
            public function handle(): void
            {
            }
        };

        $this->container->set($controller::class, $controller);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not have an invoke method');

        new InvocableClassRouteHandler($controller::class, $this->container);
    }

    public function testHandleResolvesFromContainer(): void
    {
        $controller = new class($this->responseFactory) {
            public bool $invoked = false;

            public function __construct(private ResponseFactory $responseFactory)
            {
            }

            public function __invoke(ServerRequestInterface $request): ResponseInterface
            {
                $this->invoked = true;
                return $this->responseFactory->createResponse(200);
            }
        };

        $this->container->set($controller::class, $controller);

        $handler = new InvocableClassRouteHandler($controller::class, $this->container);
        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $handler->handle($request);

        $this->assertTrue($controller->invoked);
    }
}
