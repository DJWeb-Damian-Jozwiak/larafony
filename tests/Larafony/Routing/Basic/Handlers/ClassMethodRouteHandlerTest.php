<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Basic\Handlers;

use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Routing\Basic\Handlers\ClassMethodRouteHandler;
use Larafony\Framework\Web\Application;
use Larafony\Framework\Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ClassMethodRouteHandlerTest extends TestCase
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

    public function testHandleCallsClassMethod(): void
    {
        $controller = new class($this->responseFactory, $this->streamFactory) {
            public function __construct(
                private ResponseFactory $responseFactory,
                private StreamFactory $streamFactory
            ) {
            }

            public function index(ServerRequestInterface $request): ResponseInterface
            {
                return $this->responseFactory->createResponse(200)
                    ->withBody($this->streamFactory->createStream('Controller Response'));
            }
        };

        $this->container->set($controller::class, $controller);

        $handler = new ClassMethodRouteHandler($controller::class, 'index', $this->container);
        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $response = $handler->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Controller Response', $response->getBody()->getContents());
    }

    public function testConstructorThrowsWhenClassDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class NonExistentClass does not exist');

        new ClassMethodRouteHandler('NonExistentClass', 'method', $this->container);
    }

    public function testConstructorThrowsWhenMethodDoesNotExist(): void
    {
        $controller = new class {
            public function existing(): void
            {
            }
        };

        $this->container->set($controller::class, $controller);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Method nonexistent does not exist in class');

        new ClassMethodRouteHandler($controller::class, 'nonexistent', $this->container);
    }

    public function testHandleResolvesControllerFromContainer(): void
    {
        $controller = new class($this->responseFactory) {
            public bool $called = false;

            public function __construct(private ResponseFactory $responseFactory)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->called = true;
                return $this->responseFactory->createResponse(200);
            }
        };

        $this->container->set($controller::class, $controller);

        $handler = new ClassMethodRouteHandler($controller::class, 'handle', $this->container);
        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $handler->handle($request);

        $this->assertTrue($controller->called);
    }
}
