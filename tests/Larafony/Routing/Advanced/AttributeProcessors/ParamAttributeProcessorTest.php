<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced\AttributeProcessors;

use Larafony\Framework\Http\Enums\HttpMethod;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Routing\Advanced\AttributeProcessors\ParamAttributeProcessor;
use Larafony\Framework\Routing\Advanced\Attributes\RouteParam;
use Larafony\Framework\Routing\Advanced\Route;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Web\Application;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionMethod;

class ParamAttributeProcessorTest extends TestCase
{
    private ParamAttributeProcessor $processor;
    private RouteHandlerFactory $factory;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $app = Application::instance();
        $this->factory = $app->get(RouteHandlerFactory::class);
        $this->processor = new ParamAttributeProcessor();
        $this->responseFactory = new ResponseFactory();
    }

    public function testProcessRouteParamAttribute(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<user>', HttpMethod::GET, $handler, $this->factory);
        $method = new ReflectionMethod(TestControllerForParams::class, 'showWithAttribute');

        $this->processor->process($route, $method);

        $this->assertArrayHasKey('user', $route->bindings);
        $this->assertSame('App\Models\User', $route->bindings['user']->modelClass);
    }

    public function testProcessMethodParameterTypeHint(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<user>', HttpMethod::GET, $handler, $this->factory);
        $method = new ReflectionMethod(TestControllerForParams::class, 'showWithTypeHint');

        $this->processor->process($route, $method);

        $this->assertArrayHasKey('user', $route->bindings);
        $this->assertSame(TestModel::class, $route->bindings['user']->modelClass);
    }

    public function testIgnoresBuiltinTypeHints(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id>', HttpMethod::GET, $handler, $this->factory);
        $method = new ReflectionMethod(TestControllerForParams::class, 'showWithString');

        $this->processor->process($route, $method);

        $this->assertEmpty($route->bindings);
    }

    public function testIgnoresParametersWithoutRouteMatch(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);
        $method = new ReflectionMethod(TestControllerForParams::class, 'showWithTypeHint');

        $this->processor->process($route, $method);

        $this->assertEmpty($route->bindings);
    }

    public function testProcessMultipleRouteParams(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<user>/posts/<post>', HttpMethod::GET, $handler, $this->factory);
        $method = new ReflectionMethod(TestControllerForParams::class, 'showWithMultipleAttributes');

        $this->processor->process($route, $method);

        $this->assertCount(2, $route->bindings);
        $this->assertArrayHasKey('user', $route->bindings);
        $this->assertArrayHasKey('post', $route->bindings);
    }

    public function testCombinesAttributesAndTypeHints(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<user>/posts/<post>', HttpMethod::GET, $handler, $this->factory);
        $method = new ReflectionMethod(TestControllerForParams::class, 'showWithMixed');

        $this->processor->process($route, $method);

        // Attribute should be processed first, then type hints for remaining params
        $this->assertArrayHasKey('user', $route->bindings);
        $this->assertArrayHasKey('post', $route->bindings);
    }
}

class TestControllerForParams
{
    #[RouteParam('user', bind: 'App\Models\User')]
    public function showWithAttribute(): void
    {
    }

    public function showWithTypeHint(TestModel $user): void
    {
    }

    public function showWithString(string $id): void
    {
    }

    #[RouteParam('user', bind: 'App\Models\User')]
    #[RouteParam('post', bind: 'App\Models\Post')]
    public function showWithMultipleAttributes(): void
    {
    }

    #[RouteParam('user', bind: 'App\Models\User')]
    public function showWithMixed(TestModel $post): void
    {
    }
}

class TestModel
{
    public ?string $id = null;

    public function findForRoute(string $id): ?self
    {
        $this->id = $id;
        return $this;
    }
}
