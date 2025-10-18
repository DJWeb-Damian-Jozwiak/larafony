<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Console\Commands;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Console\CommandRegistry;
use Larafony\Framework\Console\Commands\MakeMigration;
use Larafony\Framework\Console\Commands\MakeModel;
use Larafony\Framework\Console\Output;
use Larafony\Framework\Container\Container;
use Larafony\Framework\Core\Helpers\CommandCaller;
use PHPUnit\Framework\TestCase;

final class MakeModelTest extends TestCase
{
    private string $tempModelsDir;
    private string $tempMigrationsDir;

    protected function setUp(): void
    {
        // Create temp directories
        $this->tempModelsDir = sys_get_temp_dir() . '/test_make_model_' . uniqid();
        $this->tempMigrationsDir = sys_get_temp_dir() . '/test_migrations_' . uniqid();
        mkdir($this->tempModelsDir, 0777, true);
        mkdir($this->tempMigrationsDir, 0777, true);

        // Freeze time for consistent timestamps
        ClockFactory::freeze('2024-01-15 10:30:45');
    }

    protected function tearDown(): void
    {
        // Clean up temp directories
        if (is_dir($this->tempModelsDir)) {
            $this->removeDirectory($this->tempModelsDir);
        }
        if (is_dir($this->tempMigrationsDir)) {
            $this->removeDirectory($this->tempMigrationsDir);
        }

        // Reset clock
        ClockFactory::reset();
    }

    private function removeDirectory(string $dir): void
    {
        $files = glob($dir . '/*');
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_dir($file)) {
                    $this->removeDirectory($file);
                } elseif (is_file($file)) {
                    unlink($file);
                }
            }
        }
        rmdir($dir);
    }

    public function testMakeModelCreatesModelFile(): void
    {
        $config = $this->createMock(ConfigContract::class);
        $config->method('get')
            ->with('app.models.path', 'src/Models/')
            ->willReturn($this->tempModelsDir . '/');

        $output = $this->createMock(Output::class);
        $output->expects($this->once())
            ->method('info')
            ->with($this->stringContains('App\Models\User'));

        $commandCaller = $this->createMock(CommandCaller::class);

        $container = $this->createMock(Container::class);
        $container->method('get')->willReturnMap([
            [Output::class, $output],
            [ConfigContract::class, $config],
        ]);

        $command = new MakeModel($output, $container, $commandCaller);

        // Set name argument
        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('name');
        $property->setValue($command, 'User');

        $result = $command->run();

        $this->assertEquals(0, $result);

        // Check file was created
        $expectedFile = $this->tempModelsDir . '/App/Models/User.php';
        $this->assertFileExists($expectedFile);

        // Check file content
        $content = file_get_contents($expectedFile);
        $this->assertStringContainsString('namespace App\Models', $content);
        $this->assertStringContainsString('use Larafony\Framework\Database\ORM\Model', $content);
        $this->assertStringContainsString('class User extends Model', $content);
        $this->assertStringContainsString("get => 'users'", $content);
    }

    public function testMakeModelWithMigrationOptionCallsMakeMigration(): void
    {
        $config = $this->createMock(ConfigContract::class);
        $config->method('get')->willReturnCallback(function ($key, $default) {
            return match ($key) {
                'app.models.path' => $this->tempModelsDir . '/',
                'database.migrations.path' => $this->tempMigrationsDir . '/',
                default => $default,
            };
        });

        $output = $this->createMock(Output::class);

        // Create real CommandRegistry and register MakeMigration
        $registry = new CommandRegistry();
        $registry->register('make:migration', MakeMigration::class);

        // Mock container that can resolve dependencies
        $container = $this->createMock(\Larafony\Framework\Container\Contracts\ContainerContract::class);
        $container->method('get')->willReturnCallback(function ($id) use ($output, $config, $registry) {
            return match ($id) {
                Output::class => $output,
                ConfigContract::class => $config,
                CommandRegistry::class => $registry,
                MakeMigration::class => new MakeMigration($output, $this->createModelContainer($output, $config)),
                default => throw new \RuntimeException("Unexpected dependency: $id"),
            };
        });

        // Create real CommandCaller (this is the integration test!)
        $commandCaller = new CommandCaller($container, $registry);

        $command = new MakeModel($output, $container, $commandCaller);

        // Set name and migration option
        $reflection = new \ReflectionClass($command);
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setValue($command, 'Post');

        $migrationProperty = $reflection->getProperty('migration');
        $migrationProperty->setValue($command, true);

        $result = $command->run();

        $this->assertEquals(0, $result);

        // Check model file was created
        $expectedModelFile = $this->tempModelsDir . '/App/Models/Post.php';
        $this->assertFileExists($expectedModelFile);

        // Check migration file was created via CommandCaller!
        $expectedMigrationFile = $this->tempMigrationsDir . '/2024_01_15_103045_create_posts_table.php';

        // Debug: list files in migrations directory
        $files = glob($this->tempMigrationsDir . '/*') ?: [];
        $this->assertGreaterThan(
            0,
            count($files),
            "No files created in migrations directory: {$this->tempMigrationsDir}. Files found: " . implode(', ', $files)
        );

        $this->assertFileExists(
            $expectedMigrationFile,
            "Expected file not found. Files in dir: " . implode(', ', array_map('basename', $files))
        );

        // Verify migration content
        $migrationContent = file_get_contents($expectedMigrationFile);
        $this->assertStringContainsString('namespace App\Database\Migrations', $migrationContent);
        $this->assertStringContainsString('extends Migration', $migrationContent);
    }

    private function createModelContainer(Output $output, ConfigContract $config): \Larafony\Framework\Container\Contracts\ContainerContract
    {
        $container = $this->createMock(\Larafony\Framework\Container\Contracts\ContainerContract::class);
        $container->method('get')->willReturnCallback(function ($id) use ($output, $config) {
            return match ($id) {
                Output::class => $output,
                ConfigContract::class => $config,
                default => throw new \RuntimeException("Unexpected dependency: $id"),
            };
        });
        return $container;
    }

    public function testMakeModelConvertsModelNameToTableName(): void
    {
        $config = $this->createMock(ConfigContract::class);
        $config->method('get')
            ->with('app.models.path', 'src/Models/')
            ->willReturn($this->tempModelsDir . '/');

        $output = $this->createMock(Output::class);
        $commandCaller = $this->createMock(CommandCaller::class);

        $container = $this->createMock(Container::class);
        $container->method('get')->willReturnMap([
            [Output::class, $output],
            [ConfigContract::class, $config],
        ]);

        $command = new MakeModel($output, $container, $commandCaller);

        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('name');
        $property->setValue($command, 'BlogPost');

        $command->run();

        $expectedFile = $this->tempModelsDir . '/App/Models/BlogPost.php';
        $this->assertFileExists($expectedFile);

        // Check table name is correctly converted to snake_case and pluralized
        $content = file_get_contents($expectedFile);
        $this->assertStringContainsString("get => 'blog_posts'", $content);
    }

    public function testMakeModelWithoutMigrationOptionDoesNotCreateMigration(): void
    {
        $config = $this->createMock(ConfigContract::class);
        $config->method('get')
            ->with('app.models.path', 'src/Models/')
            ->willReturn($this->tempModelsDir . '/');

        $output = $this->createMock(Output::class);

        $commandCaller = $this->createMock(CommandCaller::class);
        $commandCaller->expects($this->never())->method('call');

        $container = $this->createMock(Container::class);
        $container->method('get')->willReturnMap([
            [Output::class, $output],
            [ConfigContract::class, $config],
        ]);

        $command = new MakeModel($output, $container, $commandCaller);

        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('name');
        $property->setValue($command, 'Product');

        $migrationProperty = $reflection->getProperty('migration');
        $migrationProperty->setValue($command, false);

        $result = $command->run();

        $this->assertEquals(0, $result);

        // Model should be created
        $expectedModelFile = $this->tempModelsDir . '/App/Models/Product.php';
        $this->assertFileExists($expectedModelFile);

        // But no migration should exist
        $files = glob($this->tempMigrationsDir . '/*');
        $this->assertEmpty($files ?: []);
    }
}
