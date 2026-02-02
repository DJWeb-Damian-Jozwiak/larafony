<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Larafony\Console\Commands;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\FrozenClock;
use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Console\Commands\MakeMigration;
use Larafony\Framework\Console\Output;
use Larafony\Framework\Container\Container;
use PHPUnit\Framework\TestCase;

final class MakeMigrationTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        // Create temp directory for migrations
        $this->tempDir = sys_get_temp_dir() . '/test_make_migration_' . uniqid();
        mkdir($this->tempDir, 0777, true);

        // Freeze time for consistent timestamps
        ClockFactory::freeze('2024-01-15 10:30:45');
    }

    protected function tearDown(): void
    {
        // Clean up temp directory recursively
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
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

    public function testMakeMigrationCreatesFileWithTimestamp(): void
    {
        $config = $this->createStub(ConfigContract::class);
        $config->method('get')
            ->with('database.migrations.path', 'database/migrations/')
            ->willReturn($this->tempDir);

        $output = $this->createMock(Output::class);
        $output->expects($this->once())
            ->method('info')
            ->with($this->stringContains('2024_01_15_103045_create_users_table.php'));

        $container = $this->createStub(Container::class);
        $container->method('get')->willReturnMap([
            [Output::class, $output],
            [ConfigContract::class, $config],
        ]);

        $command = new MakeMigration($output, $container);

        // Set name argument
        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('name');
        $property->setValue($command, 'create_users');

        $result = $command->run();

        $this->assertEquals(0, $result);

        // Check file was created
        $expectedFile = $this->tempDir . '/2024_01_15_103045_create_users_table.php';
        $this->assertFileExists($expectedFile);

        // Check file content
        $content = file_get_contents($expectedFile);
        $this->assertStringContainsString('namespace App\Database\Migrations', $content);
        $this->assertStringContainsString('use Larafony\Framework\Database\Base\Migrations\Migration', $content);
        $this->assertStringContainsString('return new class extends Migration', $content);
        $this->assertStringContainsString('public function up(): void', $content);
        $this->assertStringContainsString('public function down(): void', $content);
    }

    public function testMakeMigrationAppendsTableSuffixIfMissing(): void
    {
        $config = $this->createStub(ConfigContract::class);
        $config->method('get')
            ->with('database.migrations.path', 'database/migrations/')
            ->willReturn($this->tempDir);

        $output = $this->createMock(Output::class);
        $output->expects($this->once())
            ->method('info')
            ->with($this->stringContains('2024_01_15_103045_create_posts_table.php'));

        $container = $this->createStub(Container::class);
        $container->method('get')->willReturnMap([
            [Output::class, $output],
            [ConfigContract::class, $config],
        ]);

        $command = new MakeMigration($output, $container);

        // Set name argument WITHOUT _table suffix
        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('name');
        $property->setValue($command, 'create_posts');

        $result = $command->run();

        $this->assertEquals(0, $result);

        // Check file was created with _table suffix
        $expectedFile = $this->tempDir . '/2024_01_15_103045_create_posts_table.php';
        $this->assertFileExists($expectedFile);
    }

    public function testMakeMigrationDoesNotDuplicateTableSuffix(): void
    {
        $config = $this->createStub(ConfigContract::class);
        $config->method('get')
            ->with('database.migrations.path', 'database/migrations/')
            ->willReturn($this->tempDir);

        $output = $this->createMock(Output::class);
        $output->expects($this->once())
            ->method('info')
            ->with($this->stringContains('2024_01_15_103045_create_comments_table.php'));

        $container = $this->createStub(Container::class);
        $container->method('get')->willReturnMap([
            [Output::class, $output],
            [ConfigContract::class, $config],
        ]);

        $command = new MakeMigration($output, $container);

        // Set name argument WITH _table suffix already
        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('name');
        $property->setValue($command, 'create_comments_table');

        $result = $command->run();

        $this->assertEquals(0, $result);

        // Check file was created without duplicate _table suffix
        $expectedFile = $this->tempDir . '/2024_01_15_103045_create_comments_table.php';
        $this->assertFileExists($expectedFile);

        // Should NOT create a file with double _table suffix
        $wrongFile = $this->tempDir . '/2024_01_15_103045_create_comments_table_table.php';
        $this->assertFileDoesNotExist($wrongFile);
    }

    public function testMakeMigrationUsesCustomPath(): void
    {
        $customPath = $this->tempDir . '/custom/migrations';
        mkdir($customPath, 0777, true);

        $config = $this->createStub(ConfigContract::class);
        $config->method('get')
            ->with('database.migrations.path', 'database/migrations/')
            ->willReturn($customPath);

        $output = $this->createMock(Output::class);
        $output->expects($this->once())->method('info');

        $container = $this->createStub(Container::class);
        $container->method('get')->willReturnMap([
            [Output::class, $output],
            [ConfigContract::class, $config],
        ]);

        $command = new MakeMigration($output, $container);

        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('name');
        $property->setValue($command, 'create_test_table');

        $result = $command->run();

        $this->assertEquals(0, $result);

        // Check file was created in custom path
        $expectedFile = $customPath . '/2024_01_15_103045_create_test_table.php';
        $this->assertFileExists($expectedFile);
    }

    public function testMakeMigrationTimestampChangesWithTime(): void
    {
        // First migration at 10:30:45
        $config = $this->createStub(ConfigContract::class);
        $config->method('get')
            ->with('database.migrations.path', 'database/migrations/')
            ->willReturn($this->tempDir);

        $output = $this->createMock(Output::class);
        $output->expects($this->once())->method('info');

        $container = $this->createStub(Container::class);
        $container->method('get')->willReturnMap([
            [Output::class, $output],
            [ConfigContract::class, $config],
        ]);

        $command = new MakeMigration($output, $container);

        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('name');
        $property->setValue($command, 'first_migration');

        $command->run();

        // Advance time to 15:45:30
        ClockFactory::freeze('2024-01-15 15:45:30');

        // Second migration at 15:45:30
        $output2 = $this->createMock(Output::class);
        $output2->expects($this->once())->method('info');

        $container2 = $this->createStub(Container::class);
        $container2->method('get')->willReturnMap([
            [Output::class, $output2],
            [ConfigContract::class, $config],
        ]);

        $command2 = new MakeMigration($output2, $container2);

        $reflection2 = new \ReflectionClass($command2);
        $property2 = $reflection2->getProperty('name');
        $property2->setValue($command2, 'second_migration');

        $command2->run();

        // Verify different timestamps
        $file1 = $this->tempDir . '/2024_01_15_103045_first_migration_table.php';
        $file2 = $this->tempDir . '/2024_01_15_154530_second_migration_table.php';

        $this->assertFileExists($file1);
        $this->assertFileExists($file2);
    }
}
