<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced;

use Larafony\Framework\Container\Container;
use Larafony\Framework\Http\Enums\HttpMethod;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Routing\Advanced\Route;
use Larafony\Framework\Routing\Advanced\Router;
use Larafony\Framework\Routing\Basic\RouteCollection;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use Larafony\Framework\Tests\Routing\Advanced\Decorators\TestMiddleware;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Web\Application;
use Psr\Http\Message\ServerRequestInterface;

class RouterTest extends TestCase
{
    private Router $router;
    private Container $container;
    private RouteHandlerFactory $factory;
    private ResponseFactory $responseFactory;
    private ServerRequestFactory $requestFactory;
    private \Larafony\Framework\Http\Factories\StreamFactory $streamFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $app = Application::instance();
        $this->container = $app;
        $this->factory = $app->get(RouteHandlerFactory::class);
        $matcher = new \Larafony\Framework\Routing\Advanced\RouteMatcher();
        $routes = new RouteCollection($this->container, $matcher);
        $this->router = new Router($routes, $this->container);
        $this->responseFactory = new ResponseFactory();
        $this->requestFactory = new ServerRequestFactory();
        $this->streamFactory = new \Larafony\Framework\Http\Factories\StreamFactory();
    }

    public function testHandleRoutesWithoutModelBinding(): void
    {
        $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
        $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);
        $this->router->addRoute($route);

        $request = $this->requestFactory->createServerRequest('GET', '/users');
        $response = $this->router->handle($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHandleResolvesModelBindings(): void
    {
        $handler = function (ServerRequestInterface $request) {
            $params = $request->getAttribute('routeParams', []);
            $body = json_encode(['user_id' => $params['id'] ?? null]);
            return $this->responseFactory->createResponse(200)
                ->withBody($this->streamFactory->createStream($body));
        };

        $route = new Route('/users/<id>', HttpMethod::GET, $handler, $this->factory);
        $route->bind('id', TestModelForRouter::class, 'findForRoute');
        $this->router->addRoute($route);

        $this->container->set(TestModelForRouter::class, new TestModelForRouter());

        $request = $this->requestFactory->createServerRequest('GET', '/users/123');
        $response = $this->router->handle($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGroupCreatesNestedRoutes(): void
    {
        $this->router->group('/api', function ($group) {
            $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
            $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);
            $group->addRoute($route);
        });

        $this->assertCount(1, $this->router->groups);
        $this->assertCount(1, $this->router->routes->routes);
        $this->assertSame('/api/users', $this->router->routes->routes[0]->path);
    }

    public function testGroupAppliesMiddlewareBefore(): void
    {
        $this->router->group('/api', function ($group) {
            $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
            $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);
            $group->addRoute($route);
        }, [TestMiddleware::class]);

        $route = $this->router->routes->routes[0];
        $this->assertContains(TestMiddleware::class, $route->getMiddleware()->middlewareBefore);
    }

    public function testGroupAppliesMiddlewareAfter(): void
    {
        $this->router->group('/api', function ($group) {
            $handler = fn (ServerRequestInterface $request) => $this->responseFactory->createResponse(200);
            $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);
            $group->addRoute($route);
        }, [], [TestMiddleware::class]);

        $route = $this->router->routes->routes[0];
        $this->assertContains(TestMiddleware::class, $route->getMiddleware()->middlewareAfter);
    }

    public function testGroupReturnsRouter(): void
    {
        $result = $this->router->group('/api', function () {
        });

        $this->assertSame($this->router, $result);
    }

    public function testLoadAttributeRoutesRegistersRoutes(): void
    {
        $fixturesPath = __DIR__ . '/router-fixtures';

        if (! is_dir($fixturesPath)) {
            mkdir($fixturesPath, 0777, true);
        }

        $controllerContent = <<<'PHP'
<?php

namespace Larafony\Framework\Tests\Routing\Advanced\RouterFixtures;

use Larafony\Framework\Routing\Advanced\Attributes\Route;

class TestRouterController
{
    #[Route('/test')]
    public function index(): void
    {
    }
}
PHP;

        file_put_contents($fixturesPath . '/TestController.php', $controllerContent);
        require_once $fixturesPath . '/TestController.php';

        $result = $this->router->loadAttributeRoutes($fixturesPath);

        $this->assertSame($this->router, $result);
        $this->assertNotEmpty($this->router->routes->routes);

        // Cleanup
        unlink($fixturesPath . '/TestController.php');
        rmdir($fixturesPath);
    }

    public function testGroupsPropertyIsInitializedEmpty(): void
    {
        $this->assertIsArray($this->router->groups);
        $this->assertEmpty($this->router->groups);
    }
}

class TestModelForRouter
{
    public ?string $id = null;

    public function findForRoute(string $id): ?self
    {
        $this->id = $id;
        return $this;
    }
}
