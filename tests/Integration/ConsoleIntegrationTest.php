<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Integration;

use Larafony\Framework\Config\ServiceProviders\ConfigServiceProvider;
use Larafony\Framework\Console\Application;
use Larafony\Framework\Console\Formatters\Styles\InfoStyle;
use Larafony\Framework\Console\Formatters\Styles\SuccessStyle;
use Larafony\Framework\Console\Kernel;
use Larafony\Framework\Console\ServiceProviders\ConsoleServiceProvider;
use Larafony\Framework\ErrorHandler\ServiceProviders\ErrorHandlerServiceProvider;
use Larafony\Framework\Http\ServiceProviders\HttpServiceProvider;
use Larafony\Framework\Tests\TestCase;
use Psr\Http\Message\StreamInterface;

class ConsoleIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        restore_error_handler();
        restore_exception_handler();
    }

    public function testApplicationRespondsWith200(): void
    {
        $app = require __DIR__ . '/../../demo-app/bootstrap/console_app.php';
        $steam = $this->createMock(StreamInterface::class);$app->set('output_stream', $steam);
        $text = new SuccessStyle()->apply('Hello, John!').PHP_EOL;
        $steam->expects($this->once())->method('write')->with($text);
        $app->set('output_stream', $steam);
        $this->assertInstanceOf(Application::class, $app);
        $_SERVER['argv'] = ['bin/console.php', 'greet', 'John'];
        $app->handle();
        $_SERVER['argv'] = [];
    }

    public function testCommandsCacheCreatesCache(): void
    {
        // Create temp directory structure
        $tempDir = sys_get_temp_dir() . '/larafony_test_' . uniqid();
        mkdir($tempDir . '/src/Console/Commands', 0755, true);
        mkdir($tempDir . '/storage/cache', 0755, true);

        // Create a test command
        $commandContent = <<<'PHP'
<?php
namespace App\Console\Commands;
use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Command;

#[AsCommand(name: 'test:command')]
class TestCommand extends Command
{
    public function run(): int { return 0; }
}
PHP;
        file_put_contents($tempDir . '/src/Console/Commands/TestCommand.php', $commandContent);

        // Create app with temp base path
        Application::empty();
        $app = Application::instance($tempDir);
        $app->withServiceProviders([
            ErrorHandlerServiceProvider::class,
            HttpServiceProvider::class,
            ConfigServiceProvider::class,
            ConsoleServiceProvider::class,
        ]);

        // Mock output stream
        $stream = $this->createMock(StreamInterface::class);
        $app->set('output_stream', $stream);

        $_SERVER['argv'] = ['bin/console.php', 'commands:cache'];
        $exitCode = $app->handle();
        $_SERVER['argv'] = [];

        $cachePath = $tempDir . '/storage/cache/commands.php';
        $this->assertSame(0, $exitCode);
        $this->assertFileExists($cachePath);

        // Verify cache content
        $cached = require $cachePath;
        $this->assertIsArray($cached);
        $this->assertArrayHasKey('test:command', $cached);

        // Cleanup
        Application::empty();
        unlink($tempDir . '/src/Console/Commands/TestCommand.php');
        unlink($cachePath);
        rmdir($tempDir . '/storage/cache');
        rmdir($tempDir . '/storage');
        rmdir($tempDir . '/src/Console/Commands');
        rmdir($tempDir . '/src/Console');
        rmdir($tempDir . '/src');
        rmdir($tempDir);
    }

    public function testCommandsCacheClearRemovesCache(): void
    {
        // Create temp directory structure
        $tempDir = sys_get_temp_dir() . '/larafony_test_' . uniqid();
        mkdir($tempDir . '/src/Console/Commands', 0755, true);
        mkdir($tempDir . '/storage/cache', 0755, true);

        // Create cache file
        $cachePath = $tempDir . '/storage/cache/commands.php';
        file_put_contents($cachePath, "<?php\nreturn ['test' => 'TestCommand'];");
        $this->assertFileExists($cachePath);

        // Create app with temp base path
        Application::empty();
        $app = Application::instance($tempDir);
        $app->withServiceProviders([
            ErrorHandlerServiceProvider::class,
            HttpServiceProvider::class,
            ConfigServiceProvider::class,
            ConsoleServiceProvider::class,
        ]);

        // Mock output stream
        $stream = $this->createMock(StreamInterface::class);
        $app->set('output_stream', $stream);

        $_SERVER['argv'] = ['bin/console.php', 'commands:cache-clear'];
        $exitCode = $app->handle();
        $_SERVER['argv'] = [];

        $this->assertSame(0, $exitCode);
        $this->assertFileDoesNotExist($cachePath);

        // Cleanup
        Application::empty();
        rmdir($tempDir . '/storage/cache');
        rmdir($tempDir . '/storage');
        rmdir($tempDir . '/src/Console/Commands');
        rmdir($tempDir . '/src/Console');
        rmdir($tempDir . '/src');
        rmdir($tempDir);
    }
}