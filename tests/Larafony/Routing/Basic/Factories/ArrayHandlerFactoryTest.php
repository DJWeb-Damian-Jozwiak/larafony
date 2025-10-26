<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Basic\Factories;

use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Routing\Basic\Factories\ArrayHandlerFactory;
use Larafony\Framework\Routing\Basic\Handlers\ClassMethodRouteHandler;
use Larafony\Framework\Web\Application;
use Larafony\Framework\Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ArrayHandlerFactoryTest extends TestCase
{
    private Application $container;
    private ArrayHandlerFactory $factory;
    private ServerRequestFactory $requestFactory;
    private ResponseFactory $responseFactory;
    private StreamFactory $streamFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = Application::instance();
        $this->factory = new ArrayHandlerFactory($this->container);
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
        $this->streamFactory = new StreamFactory();
    }

    public function testCreateReturnsClassMethodHandler(): void
    {
        $controller = new class($this->responseFactory, $this->streamFactory) {
            public function __construct(
                private ResponseFactory $responseFactory,
                private StreamFactory $streamFactory
            ) {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->responseFactory->createResponse(200)
                    ->withBody($this->streamFactory->createStream('Array Handler Response'));
            }
        };

        $this->container->set($controller::class, $controller);

        $handler = $this->factory->create([$controller::class, 'handle']);

        // Now returns FormRequestAwareHandler which supports both ServerRequest and FormRequest DTOs
        $this->assertInstanceOf(\Larafony\Framework\Validation\Handlers\FormRequestAwareHandler::class, $handler);

        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $response = $handler->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Array Handler Response', $response->getBody()->getContents());
    }

    public function testCreateThrowsWithInvalidArrayLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array handler must contain exactly 2 elements: [class, method]');

        $this->factory->create(['OnlyOneElement']);
    }

    public function testCreateThrowsWithTooManyElements(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array handler must contain exactly 2 elements: [class, method]');

        $this->factory->create(['Class', 'method', 'extra']);
    }

    public function testCreateThrowsWhenFirstElementNotString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array handler must contain strings: [class, method]');

        $this->factory->create([123, 'method']);
    }

    public function testCreateThrowsWhenSecondElementNotString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array handler must contain strings: [class, method]');

        $this->factory->create(['Class', 456]);
    }

    public function testCreateThrowsWhenBothElementsNotStrings(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array handler must contain strings: [class, method]');

        $this->factory->create([123, 456]);
    }
}
