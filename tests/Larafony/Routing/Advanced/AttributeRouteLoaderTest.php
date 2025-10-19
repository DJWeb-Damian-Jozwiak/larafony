<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced;

use Larafony\Framework\Routing\Advanced\AttributeRouteLoader;
use Larafony\Framework\Routing\Advanced\AttributeRouteScanner;
use Larafony\Framework\Routing\Advanced\Attributes\Route as RouteAttribute;
use Larafony\Framework\Routing\Advanced\Route;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Web\Application;
use ReflectionClass;

class AttributeRouteLoaderTest extends TestCase
{
    private AttributeRouteLoader $loader;
    private string $fixturesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $app = Application::instance();
        $scanner = new AttributeRouteScanner();
        $factory = $app->get(RouteHandlerFactory::class);
        $this->loader = new AttributeRouteLoader($scanner, $factory);
        $this->fixturesPath = __DIR__ . '/loader-fixtures';

        if (! is_dir($this->fixturesPath)) {
            mkdir($this->fixturesPath, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->fixturesPath)) {
            $files = glob($this->fixturesPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->fixturesPath);
        }
    }

    public function testLoadFromDirectoryReturnsRoutes(): void
    {
        $this->createFixture('Controller.php', $this->getControllerContent());

        $routes = $this->loader->loadFromDirectory($this->fixturesPath);

        $this->assertNotEmpty($routes);
        $this->assertContainsOnlyInstancesOf(Route::class, $routes);
    }

    public function testLoadFromControllerCreatesRouteForEachMethod(): void
    {
        $this->createFixture('Controller.php', $this->getControllerContent());
        require_once $this->fixturesPath . '/Controller.php';

        $reflection = new ReflectionClass('Larafony\Framework\Tests\Routing\Advanced\LoaderFixtures\TestLoaderController');
        $routes = $this->loader->loadFromController($reflection);

        $this->assertCount(2, $routes); // One for GET, one for POST from single method
    }

    public function testLoadFromControllerHandlesMultipleHttpMethods(): void
    {
        $this->createFixture('MultiMethodController.php', $this->getControllerWithMultipleMethodsContent());
        require_once $this->fixturesPath . '/MultiMethodController.php';

        $reflection = new ReflectionClass('Larafony\Framework\Tests\Routing\Advanced\LoaderFixtures\MultiMethodController');
        $routes = $this->loader->loadFromController($reflection);

        $this->assertCount(2, $routes);
        $methods = array_map(fn ($route) => $route->method->value, $routes);
        $this->assertContains('GET', $methods);
        $this->assertContains('POST', $methods);
    }

    public function testLoadFromControllerSetsCorrectPath(): void
    {
        $this->createFixture('Controller.php', $this->getControllerContent());
        require_once $this->fixturesPath . '/Controller.php';

        $reflection = new ReflectionClass('Larafony\Framework\Tests\Routing\Advanced\LoaderFixtures\TestLoaderController');
        $routes = $this->loader->loadFromController($reflection);

        $this->assertSame('/users', $routes[0]->path);
    }

    public function testLoadFromControllerIgnoresNonPublicMethods(): void
    {
        $this->createFixture('PrivateMethodController.php', $this->getControllerWithPrivateMethodContent());
        require_once $this->fixturesPath . '/PrivateMethodController.php';

        $reflection = new ReflectionClass('Larafony\Framework\Tests\Routing\Advanced\LoaderFixtures\PrivateMethodController');
        $routes = $this->loader->loadFromController($reflection);

        $this->assertEmpty($routes);
    }

    private function createFixture(string $filename, string $content): void
    {
        file_put_contents($this->fixturesPath . '/' . $filename, $content);
        require_once $this->fixturesPath . '/' . $filename;
    }

    private function getControllerContent(): string
    {
        return <<<'PHP'
<?php

namespace Larafony\Framework\Tests\Routing\Advanced\LoaderFixtures;

use Larafony\Framework\Routing\Advanced\Attributes\Route;

class TestLoaderController
{
    #[Route('/users', ['GET', 'POST'])]
    public function index(): void
    {
    }
}
PHP;
    }

    private function getControllerWithMultipleMethodsContent(): string
    {
        return <<<'PHP'
<?php

namespace Larafony\Framework\Tests\Routing\Advanced\LoaderFixtures;

use Larafony\Framework\Routing\Advanced\Attributes\Route;

class MultiMethodController
{
    #[Route('/test', ['GET', 'POST'])]
    public function index(): void
    {
    }
}
PHP;
    }

    private function getControllerWithPrivateMethodContent(): string
    {
        return <<<'PHP'
<?php

namespace Larafony\Framework\Tests\Routing\Advanced\LoaderFixtures;

use Larafony\Framework\Routing\Advanced\Attributes\Route;

class PrivateMethodController
{
    #[Route('/private')]
    private function index(): void
    {
    }
}
PHP;
    }
}
