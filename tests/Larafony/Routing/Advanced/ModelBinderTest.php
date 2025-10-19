<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced;

use Larafony\Framework\Container\Container;
use Larafony\Framework\Http\Enums\HttpMethod;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Routing\Advanced\ModelBinder;
use Larafony\Framework\Routing\Advanced\Route;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Web\Application;
use Psr\Http\Message\ServerRequestInterface;

class ModelBinderTest extends TestCase
{
    private ModelBinder $binder;
    private Container $container;
    private RouteHandlerFactory $factory;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $app = Application::instance();
        $this->container = $app;
        $this->factory = $app->get(RouteHandlerFactory::class);
        $this->binder = new ModelBinder($this->container);
        $this->responseFactory = new ResponseFactory();
    }

    public function testResolveBindingsWithExplicitBinding(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id>', HttpMethod::GET, $handler, $this->factory);
        $route->withParameters(['id' => '123']);
        $route->bind('id', TestModel::class, 'findForRoute');

        // Register the model in the container
        $model = new TestModel();
        $this->container->set(TestModel::class, $model);

        $result = $this->binder->resolveBindings($route);

        $this->assertArrayHasKey('id', $result);
        $this->assertInstanceOf(TestModel::class, $result['id']);
        $this->assertSame('123', $result['id']->id);
    }

    public function testResolveBindingsPreservesNonBoundParameters(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id>', HttpMethod::GET, $handler, $this->factory);
        $route->withParameters(['id' => '123', 'other' => 'value']);
        $route->bind('id', TestModel::class, 'findForRoute');

        $model = new TestModel();
        $this->container->set(TestModel::class, $model);

        $result = $this->binder->resolveBindings($route);

        $this->assertArrayHasKey('other', $result);
        $this->assertSame('value', $result['other']);
    }

    public function testResolveBindingsWithMultipleBindings(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<userId>/posts/<postId>', HttpMethod::GET, $handler, $this->factory);
        $route->withParameters(['userId' => '1', 'postId' => '2']);
        $route->bind('userId', TestModel::class, 'findForRoute');
        $route->bind('postId', AnotherTestModel::class, 'findForRoute');

        $this->container->set(TestModel::class, new TestModel());
        $this->container->set(AnotherTestModel::class, new AnotherTestModel());

        $result = $this->binder->resolveBindings($route);

        $this->assertArrayHasKey('userId', $result);
        $this->assertArrayHasKey('postId', $result);
        $this->assertInstanceOf(TestModel::class, $result['userId']);
        $this->assertInstanceOf(AnotherTestModel::class, $result['postId']);
        $this->assertSame('1', $result['userId']->id);
        $this->assertSame('2', $result['postId']->id);
    }

    public function testResolveFromMethodSignatureWithTypeHint(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<user>', HttpMethod::GET, $handler, $this->factory);
        $route->withParameters(['user' => '123']);

        $this->container->set(TestModel::class, new TestModel());

        $result = $this->binder->resolveFromMethodSignature($route, TestController::class, 'show');

        $this->assertArrayHasKey('user', $result);
        $this->assertInstanceOf(TestModel::class, $result['user']);
        $this->assertSame('123', $result['user']->id);
    }

    public function testResolveFromMethodSignatureIgnoresBuiltinTypes(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<id>', HttpMethod::GET, $handler, $this->factory);
        $route->withParameters(['id' => '123']);

        $result = $this->binder->resolveFromMethodSignature($route, TestController::class, 'index');

        $this->assertSame('123', $result['id']);
    }

    public function testResolveFromMethodSignatureIgnoresNonRouteParameters(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);
        $route->withParameters([]);

        $result = $this->binder->resolveFromMethodSignature($route, TestController::class, 'create');

        $this->assertEmpty($result);
    }

    public function testResolveFromMethodSignatureSkipsAlreadyBound(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users/<user>', HttpMethod::GET, $handler, $this->factory);
        $route->withParameters(['user' => '123']);
        $route->bind('user', AnotherTestModel::class); // Already bound

        $this->container->set(AnotherTestModel::class, new AnotherTestModel());

        $result = $this->binder->resolveFromMethodSignature($route, TestController::class, 'show');

        // Should not override existing binding
        $this->assertSame('123', $result['user']);
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

class AnotherTestModel
{
    public ?string $id = null;

    public function findForRoute(string $id): ?self
    {
        $this->id = $id;
        return $this;
    }
}

class TestController
{
    public function index(string $id): void
    {
    }

    public function show(TestModel $user): void
    {
    }

    public function create(): void
    {
    }
}
