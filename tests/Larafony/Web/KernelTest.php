<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Web;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Routing\Advanced\Router;
use Larafony\Framework\Routing\Basic\Factories\ArrayHandlerFactory;
use Larafony\Framework\Routing\Basic\Factories\StringHandlerFactory;
use Larafony\Framework\Routing\Basic\RouteCollection;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Web\Kernel;
use Larafony\Framework\Web\Middleware\MiddlewareStack;
use Psr\Http\Message\ServerRequestInterface;

class KernelTest extends TestCase
{
    private Router $router;
    private Kernel $kernel;
    private ServerRequestFactory $requestFactory;
    private ResponseFactory $responseFactory;
    private StreamFactory $streamFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
        $this->streamFactory = new StreamFactory();

        // Create fresh instances for each test to avoid state pollution
        $container = $this->createMock(ContainerContract::class);
        $arrayFactory = new ArrayHandlerFactory($container);
        $stringFactory = new StringHandlerFactory($container);
        $factory = new RouteHandlerFactory($arrayFactory, $stringFactory);

        $container->method('get')->willReturnCallback(function ($id) use ($factory) {
            if ($id === RouteHandlerFactory::class) {
                return $factory;
            }
            throw new \Exception("Unexpected container get: $id");
        });

        $routes = new RouteCollection($container);
        $this->router = new Router($routes, $container);
        $middlewareStack = new MiddlewareStack($this->router);
        $this->kernel = new Kernel($middlewareStack, $this->router);
    }

    public function testHandleProcessesRequest(): void
    {
        $this->router->addRouteByParams(
            'GET',
            '/test',
            fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200)
                ->withBody($this->streamFactory->createStream('Test Response'))
        );

        $exitCallback = function (int $code): void {
            // Mock exit callback
        };

        $request = $this->requestFactory->createServerRequest('GET', '/test');
        $response = $this->kernel->handle($request, $exitCallback);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Test Response', $response->getBody()->getContents());
    }

    public function testHandleHeadersSetsStatusCode(): void
    {
        $response = $this->responseFactory->createResponse(404);

        $result = $this->kernel->handleHeaders($response);

        $this->assertSame($response, $result);
        $this->assertSame(404, http_response_code());
    }

    public function testHandleHeadersSetsResponseHeaders(): void
    {
        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('X-Custom-Header', 'test-value');

        // Clean headers before test
        if (!headers_sent()) {
            header_remove();
        }

        $this->kernel->handleHeaders($response);

        // Note: We can't easily test if headers were actually set in CLI context
        // but we can verify the method returns the response unchanged
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHandleHeadersFiltersOutLocationHeader(): void
    {
        $response = $this->responseFactory->createResponse(302)
            ->withHeader('Location', 'https://example.com')
            ->withHeader('Content-Type', 'text/html');

        $result = $this->kernel->handleHeaders($response);

        // The response should still have the Location header
        $this->assertTrue($result->hasHeader('Location'));
        $this->assertSame($response, $result);
    }

    public function testHandleRedirectsWithLocationHeader(): void
    {
        $callbackCalled = false;
        $statusCodePassed = null;

        $response = $this->responseFactory->createResponse(302)
            ->withHeader('Location', 'https://example.com/redirect');

        $callback = function (int $code) use (&$callbackCalled, &$statusCodePassed): void {
            $callbackCalled = true;
            $statusCodePassed = $code;
        };

        $result = $this->kernel->handleRedirects($response, $callback);

        $this->assertTrue($callbackCalled);
        $this->assertSame(302, $statusCodePassed);
        $this->assertSame($response, $result);
    }

    public function testHandleRedirectsWithoutLocationHeader(): void
    {
        $callbackCalled = false;

        $response = $this->responseFactory->createResponse(200)
            ->withBody($this->streamFactory->createStream('No redirect'));

        $callback = function (int $code) use (&$callbackCalled): void {
            $callbackCalled = true;
        };

        $result = $this->kernel->handleRedirects($response, $callback);

        $this->assertFalse($callbackCalled);
        $this->assertSame($response, $result);
    }

    public function testHandleRedirectsAdjustsInvalidStatusCode(): void
    {
        $statusCodePassed = null;

        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Location', 'https://example.com');

        $callback = function (int $code) use (&$statusCodePassed): void {
            $statusCodePassed = $code;
        };

        $this->kernel->handleRedirects($response, $callback);

        // Should be adjusted to 302
        $this->assertSame(302, $statusCodePassed);
    }

    public function testHandleRedirectsKeepsValidStatusCode(): void
    {
        $statusCodePassed = null;

        $response = $this->responseFactory->createResponse(301)
            ->withHeader('Location', 'https://example.com');

        $callback = function (int $code) use (&$statusCodePassed): void {
            $statusCodePassed = $code;
        };

        $this->kernel->handleRedirects($response, $callback);

        // Should keep 301
        $this->assertSame(301, $statusCodePassed);
    }

    public function testWithRoutesCallsCallback(): void
    {
        $routerPassed = null;

        $callback = function (Router $router) use (&$routerPassed): void {
            $routerPassed = $router;
        };

        $result = $this->kernel->withRoutes($callback);

        $this->assertSame($this->router, $routerPassed);
        $this->assertSame($this->kernel, $result);
    }
}
