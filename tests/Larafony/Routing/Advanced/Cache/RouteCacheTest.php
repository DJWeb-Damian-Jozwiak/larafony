<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced\Cache;

use Larafony\Framework\Http\Enums\HttpMethod;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Routing\Advanced\Cache\RouteCache;
use Larafony\Framework\Routing\Advanced\Route;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Web\Application;
use Psr\Http\Message\ServerRequestInterface;

class RouteCacheTest extends TestCase
{
    private RouteCache $cache;
    private string $cacheDir;
    private RouteHandlerFactory $factory;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sys_get_temp_dir() . '/larafony-test-cache-' . uniqid();
        $this->cache = new RouteCache($this->cacheDir);

        $app = Application::instance();
        $this->factory = $app->get(RouteHandlerFactory::class);
        $this->responseFactory = new ResponseFactory();
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
        if (is_dir($this->cacheDir)) {
            rmdir($this->cacheDir);
        }

        parent::tearDown();
    }

    public function testGetReturnsNullWhenCacheEmpty(): void
    {
        $result = $this->cache->get('/some/path');

        $this->assertNull($result);
    }

    public function testPutAndGetStoresRoutes(): void
    {
        // Use array handler instead of Closure for serialization
        $handler = [TestCacheController::class, 'index'];
        $route = new Route('/users', HttpMethod::GET, $handler, $this->factory, 'users.index');
        $routes = [$route];

        $this->cache->put('/app/controllers', $routes);
        $result = $this->cache->get('/app/controllers');

        $this->assertNotNull($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Route::class, $result[0]);
        $this->assertSame('/users', $result[0]->path);
    }

    public function testClearRemovesCacheFile(): void
    {
        $handler = [TestCacheController::class, 'index'];
        $route = new Route('/users', HttpMethod::GET, $handler, $this->factory);
        $routes = [$route];

        $this->cache->put('/app/controllers', $routes);
        $this->cache->clear();
        $result = $this->cache->get('/app/controllers');

        $this->assertNull($result);
    }

    public function testSupportsMultiplePaths(): void
    {
        $handler = [TestCacheController::class, 'index'];
        $route1 = new Route('/users', HttpMethod::GET, $handler, $this->factory);
        $route2 = new Route('/posts', HttpMethod::GET, $handler, $this->factory);

        $this->cache->put('/app/controllers', [$route1]);
        $this->cache->put('/app/admin', [$route2]);

        $result1 = $this->cache->get('/app/controllers');
        $result2 = $this->cache->get('/app/admin');

        $this->assertCount(1, $result1);
        $this->assertCount(1, $result2);
        $this->assertSame('/users', $result1[0]->path);
        $this->assertSame('/posts', $result2[0]->path);
    }
}

class TestCacheController
{
    public function index(): void
    {
    }
}
