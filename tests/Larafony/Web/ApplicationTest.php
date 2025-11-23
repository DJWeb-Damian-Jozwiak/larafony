<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Web;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;
use Larafony\Framework\Routing\Basic\Router;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Web\Application;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;


class ApplicationTest extends TestCase
{
    private Application $app;

    public function testInstanceCreatesSingleton(): void
    {
        $app1 = Application::instance();
        $app2 = Application::instance();

        $this->assertSame($app1, $app2);
    }

    public function testInstanceWithBasePath(): void
    {
        $basePath = '/var/www/app';
        $app = Application::instance($basePath);

        $this->assertSame($basePath, $app->base_path);
    }

    public function testConstructorBindsContainerContract(): void
    {
        $app = Application::instance();

        $this->assertTrue($app->has(ContainerContract::class));
        $this->assertSame($app, $app->get(ContainerContract::class));
    }

    public function testConstructorDoesNotBindBasePathWhenNull(): void
    {
        $app = Application::instance(null);

        $this->assertFalse($app->has('base_path'));
    }

    public function testWithRoutesCallsKernel(): void
    {
        $app = Application::instance();
        $routesCalled = false;

        $callback = function (Router $router) use (&$routesCalled): void {
            $routesCalled = true;
        };

        $result = $app->withRoutes($callback);

        $this->assertTrue($routesCalled);
        $this->assertSame($app, $result);
    }

    public function testWithServiceProviders()
    {
        $app = Application::instance();
        $app->withServiceProviders([
            HttpServiceProvider::class
        ]);
        $this->assertTrue($app->has(RequestFactoryInterface::class));
    }

    public function testRunHandlesRequestAndOutputsResponse(): void
    {
        $app = Application::instance();
        $app->withServiceProviders([HttpServiceProvider::class]);

        // Set up a simple route that returns a response
        $app->withRoutes(function (Router $router): void {
            $router->addRouteByParams(
                'GET',
                '/',
                fn (ServerRequestInterface $request) => new ResponseFactory()
                    ->createResponse(200)
                    ->withBody(new \Larafony\Framework\Http\Factories\StreamFactory()->createStream('Hello World')),
            );
        });

        // Mock exit callback to prevent actual exit
        $exitCallback = function (int $code): void {
            // Do nothing, just prevent exit
        };

        // Capture output
        ob_start();
        $app->run($exitCallback);
        $output = ob_get_clean();

        $this->assertSame('Hello World', $output);
    }
}
