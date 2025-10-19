<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced;

use Larafony\Framework\Routing\Advanced\AttributeRouteScanner;
use Larafony\Framework\Routing\Advanced\Attributes\Route;
use Larafony\Framework\Tests\TestCase;
use ReflectionClass;

class AttributeRouteScannerTest extends TestCase
{
    private AttributeRouteScanner $scanner;
    private string $fixturesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scanner = new AttributeRouteScanner();
        $this->fixturesPath = __DIR__ . '/fixtures';

        // Create fixtures directory if not exists
        if (! is_dir($this->fixturesPath)) {
            mkdir($this->fixturesPath, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up fixture files
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

    public function testScanDirectoryFindsControllerFiles(): void
    {
        $this->createFixtureFile('TestController.php', $this->getControllerWithRouteContent());

        $classes = $this->scanner->scanDirectory($this->fixturesPath);

        $this->assertNotEmpty($classes);
        $this->assertContainsOnlyInstancesOf(ReflectionClass::class, $classes);
    }

    public function testScanDirectoryIgnoresNonPhpFiles(): void
    {
        file_put_contents($this->fixturesPath . '/readme.txt', 'Not a PHP file');
        $this->createFixtureFile('TestController.php', $this->getControllerWithRouteContent());

        $classes = $this->scanner->scanDirectory($this->fixturesPath);

        $this->assertCount(1, $classes);
    }

    public function testScanDirectoryIgnoresClassesWithoutRouteAttributes(): void
    {
        $this->createFixtureFile('NoRoutes.php', $this->getControllerWithoutRoutesContent());

        $classes = $this->scanner->scanDirectory($this->fixturesPath);

        $this->assertEmpty($classes);
    }

    public function testHasRouteAttributesReturnsTrueForClassWithMethodRoutes(): void
    {
        $this->createFixtureFile('TestController.php', $this->getControllerWithRouteContent());
        $classes = $this->scanner->scanDirectory($this->fixturesPath);

        $this->assertNotEmpty($classes);
        $hasAttributes = $this->scanner->hasRouteAttributes($classes[0]);

        $this->assertTrue($hasAttributes);
    }

    public function testHasRouteAttributesReturnsTrueForClassAttributes(): void
    {
        $this->createFixtureFile('TestGroupController.php', $this->getControllerWithClassAttributesContent());

        // Manually create reflection since scanDirectory may filter differently
        $reflection = new \ReflectionClass('Larafony\Framework\Tests\Routing\Advanced\Fixtures\TestGroupController');
        $hasAttributes = $this->scanner->hasRouteAttributes($reflection);

        $this->assertTrue($hasAttributes);
    }

    public function testHasRouteAttributesReturnsFalseForClassWithoutAttributes(): void
    {
        $this->createFixtureFile('NoRoutes.php', $this->getControllerWithoutRoutesContent());

        // We need to require the file to make the class available
        require_once $this->fixturesPath . '/NoRoutes.php';

        $reflection = new ReflectionClass('Larafony\Framework\Tests\Routing\Advanced\Fixtures\NoRoutesController');
        $hasAttributes = $this->scanner->hasRouteAttributes($reflection);

        $this->assertFalse($hasAttributes);
    }

    private function createFixtureFile(string $filename, string $content): void
    {
        file_put_contents($this->fixturesPath . '/' . $filename, $content);
        require_once $this->fixturesPath . '/' . $filename;
    }

    private function getControllerWithRouteContent(): string
    {
        return <<<'PHP'
<?php

namespace Larafony\Framework\Tests\Routing\Advanced\Fixtures;

use Larafony\Framework\Routing\Advanced\Attributes\Route;

class TestScanController
{
    #[Route('/test')]
    public function index(): void
    {
    }
}
PHP;
    }

    private function getControllerWithClassAttributesContent(): string
    {
        return <<<'PHP'
<?php

namespace Larafony\Framework\Tests\Routing\Advanced\Fixtures;

use Larafony\Framework\Routing\Advanced\Attributes\RouteGroup;

#[RouteGroup('/api')]
class TestGroupController
{
    public function index(): void
    {
    }
}
PHP;
    }

    private function getControllerWithoutRoutesContent(): string
    {
        return <<<'PHP'
<?php

namespace Larafony\Framework\Tests\Routing\Advanced\Fixtures;

class NoRoutesController
{
    public function index(): void
    {
    }
}
PHP;
    }
}
