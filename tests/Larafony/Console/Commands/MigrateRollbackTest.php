<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Larafony\Console\Commands;

use Larafony\Framework\Console\Commands\MigrateRollback;
use Larafony\Framework\Console\Output;
use Larafony\Framework\Container\Container;
use Larafony\Framework\Database\Base\Migrations\Migration;
use Larafony\Framework\Database\Base\Migrations\MigrationExecutor;
use Larafony\Framework\Database\Base\Migrations\MigrationResolver;
use Larafony\Framework\Database\Drivers\MySQL\Migrations\MigrationRepository;
use PHPUnit\Framework\TestCase;

final class MigrateRollbackTest extends TestCase
{
    private string $migrationPath;

    protected function setUp(): void
    {
        // Create temp migration directory
        $this->migrationPath = sys_get_temp_dir() . '/test_migrations_' . uniqid();
        mkdir($this->migrationPath, 0777, true);
    }

    protected function tearDown(): void
    {
        // Clean up temp migrations
        if (is_dir($this->migrationPath)) {
            $files = glob($this->migrationPath . '/*');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            rmdir($this->migrationPath);
        }
    }

    public function testRollbackWithNoMigrations(): void
    {
        // Create stub repository that returns 0 for last batch
        $repository = new class extends MigrationRepository {
            public function __construct() {}
            public function createMigrationsTable(): void {}
            public function getLastBatchNumber(): int { return 0; }
            public function getMigrationsByBatch(int $batch): array { return []; }
            protected function queryBuilder(): never { throw new \Exception('Should not be called'); }
        };

        $output = $this->createMock(Output::class);
        $output->expects($this->once())
            ->method('info')
            ->with('Nothing to rollback');

        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($output);

        $resolver = new MigrationResolver($this->migrationPath);
        $executor = new MigrationExecutor($resolver, $repository);

        $command = new MigrateRollback($container, $repository, $executor);
        $result = $command->run();

        $this->assertEquals(0, $result);
    }

    public function testRollbackLastBatch(): void
    {
        $migrationName = '2024_01_01_000000_create_test';
        $executed = [];

        // Create stub repository
        $repository = new class($migrationName, $executed) extends MigrationRepository {
            public function __construct(
                private string $migrationName,
                private array &$executed
            ) {}
            public function createMigrationsTable(): void {}
            public function getLastBatchNumber(): int { return 1; }
            public function getMigrationsByBatch(int $batch): array {
                return $batch === 1 ? [$this->migrationName] : [];
            }
            public function delete(string $migration): void {
                $this->executed[] = "deleted_{$migration}";
            }
            protected function queryBuilder(): never { throw new \Exception('Should not be called'); }
        };

        // Create test migration file
        $this->createMigrationFile($migrationName, <<<'PHP'
return new class extends \Larafony\Framework\Database\Base\Migrations\Migration {
    public function up(): void {
        // Mock up
    }
    public function down(): void {
        // Mock down - executed during rollback
    }
};
PHP);

        $output = $this->createMock(Output::class);
        $output->expects($this->once())
            ->method('info')
            ->with("Rolled back: {$migrationName}");

        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($output);

        $resolver = new MigrationResolver($this->migrationPath);
        $executor = new MigrationExecutor($resolver, $repository);

        $command = new MigrateRollback($container, $repository, $executor);
        $result = $command->run();

        $this->assertEquals(0, $result);
        $this->assertContains("deleted_{$migrationName}", $executed);
    }

    public function testRollbackMultipleBatchesWithStep(): void
    {
        $migration1 = '2024_01_01_000000_create_users';
        $migration2 = '2024_01_02_000000_create_posts';
        $executed = [];

        // Create stub repository
        $repository = new class($migration1, $migration2, $executed) extends MigrationRepository {
            public function __construct(
                private string $migration1,
                private string $migration2,
                private array &$executed
            ) {}
            public function createMigrationsTable(): void {}
            public function getLastBatchNumber(): int { return 2; }
            public function getMigrationsByBatch(int $batch): array {
                return match ($batch) {
                    2 => [$this->migration2],
                    1 => [$this->migration1],
                    default => [],
                };
            }
            public function delete(string $migration): void {
                $this->executed[] = $migration;
            }
            protected function queryBuilder(): never { throw new \Exception('Should not be called'); }
        };

        // Create migration files
        $this->createMigrationFile($migration1, <<<'PHP'
return new class extends \Larafony\Framework\Database\Base\Migrations\Migration {
    public function up(): void {}
    public function down(): void {}
};
PHP);

        $this->createMigrationFile($migration2, <<<'PHP'
return new class extends \Larafony\Framework\Database\Base\Migrations\Migration {
    public function up(): void {}
    public function down(): void {}
};
PHP);

        $output = $this->createMock(Output::class);
        $output->expects($this->exactly(2))->method('info');

        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($output);

        $resolver = new MigrationResolver($this->migrationPath);
        $executor = new MigrationExecutor($resolver, $repository);

        $command = new MigrateRollback($container, $repository, $executor);

        // Set step to 2
        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('step');
        $property->setValue($command, 2);

        $result = $command->run();

        $this->assertEquals(0, $result);
        $this->assertCount(2, $executed);
        $this->assertContains($migration1, $executed);
        $this->assertContains($migration2, $executed);
    }

    private function createMigrationFile(string $name, string $content): void
    {
        $path = $this->migrationPath . '/' . $name . '.php';
        file_put_contents($path, "<?php\n\n" . $content);
    }
}
