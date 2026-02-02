<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Larafony\Console\Commands;

use Larafony\Framework\Console\Commands\Migrate;
use Larafony\Framework\Console\Output;
use Larafony\Framework\Container\Container;
use Larafony\Framework\Database\Base\Migrations\MigrationExecutor;
use Larafony\Framework\Database\Base\Migrations\MigrationResolver;
use Larafony\Framework\Database\Drivers\MySQL\Migrations\MigrationRepository;
use PHPUnit\Framework\TestCase;

final class MigrateTest extends TestCase
{
    private string $migrationPath;

    protected function setUp(): void
    {
        // Create temp migration directory
        $this->migrationPath = sys_get_temp_dir() . '/test_migrations_migrate_' . uniqid();
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

    public function testMigrateWithNoPendingMigrations(): void
    {
        // Create stub repository that returns all migrations as already ran
        $repository = new class extends MigrationRepository {
            public function __construct() {}
            public function createMigrationsTable(): void {}
            public function getRan(): array { return ['2024_01_01_000000_create_users']; }
            protected function queryBuilder(): never { throw new \Exception('Should not be called'); }
        };

        $output = $this->createMock(Output::class);
        $output->expects($this->once())
            ->method('info')
            ->with('Nothing to migrate');

        $container = $this->createStub(Container::class);
        $container->method('get')->willReturn($output);

        // Create a migration file but mark it as already ran
        $this->createMigrationFile('2024_01_01_000000_create_users', <<<'PHP'
return new class extends \Larafony\Framework\Database\Base\Migrations\Migration {
    public function up(): void {}
    public function down(): void {}
};
PHP);

        $resolver = new MigrationResolver($this->migrationPath);
        $executor = new MigrationExecutor($resolver, $repository);

        $command = new Migrate($container, $repository, $resolver, $executor);
        $result = $command->run();

        $this->assertEquals(0, $result);
    }

    public function testMigrateRunsPendingMigrations(): void
    {
        $tracker = new \stdClass();
        $tracker->migratedFiles = [];

        // Create stub repository that returns no previously ran migrations
        $repository = new class($tracker) extends MigrationRepository {
            public function __construct(private \stdClass $tracker) {}
            public function createMigrationsTable(): void {}
            public function getRan(): array { return []; }
            public function log(string $migration): void {
                $this->tracker->migratedFiles[] = $migration;
            }
            protected function queryBuilder(): never { throw new \Exception('Should not be called'); }
        };

        $output = $this->createMock(Output::class);
        $output->expects($this->once())
            ->method('info')
            ->with('Migrated: 2024_01_01_000000_create_users');

        $container = $this->createStub(Container::class);
        $container->method('get')->willReturn($output);

        $migrationName = '2024_01_01_000000_create_users';
        $this->createMigrationFile($migrationName, <<<'PHP'
return new class extends \Larafony\Framework\Database\Base\Migrations\Migration {
    public function up(): void {}
    public function down(): void {}
};
PHP);

        $resolver = new MigrationResolver($this->migrationPath);
        $executor = new MigrationExecutor($resolver, $repository);

        $command = new Migrate($container, $repository, $resolver, $executor);
        $result = $command->run();

        $this->assertEquals(0, $result);
        $this->assertContains($migrationName, $tracker->migratedFiles);
    }

    public function testMigrateWithStepOption(): void
    {
        $tracker = new \stdClass();
        $tracker->migratedFiles = [];

        $repository = new class($tracker) extends MigrationRepository {
            public function __construct(private \stdClass $tracker) {}
            public function createMigrationsTable(): void {}
            public function getRan(): array { return []; }
            public function log(string $migration): void {
                $this->tracker->migratedFiles[] = $migration;
            }
            protected function queryBuilder(): never { throw new \Exception('Should not be called'); }
        };

        $output = $this->createMock(Output::class);
        // Expect only 2 migrations (step = 2)
        $output->expects($this->exactly(2))->method('info');

        $container = $this->createStub(Container::class);
        $container->method('get')->willReturn($output);

        // Create 3 migrations
        $migration1 = '2024_01_01_000000_create_users';
        $migration2 = '2024_01_02_000000_create_posts';
        $migration3 = '2024_01_03_000000_create_comments';

        foreach ([$migration1, $migration2, $migration3] as $name) {
            $this->createMigrationFile($name, <<<'PHP'
return new class extends \Larafony\Framework\Database\Base\Migrations\Migration {
    public function up(): void {}
    public function down(): void {}
};
PHP);
        }

        $resolver = new MigrationResolver($this->migrationPath);
        $executor = new MigrationExecutor($resolver, $repository);

        $command = new Migrate($container, $repository, $resolver, $executor);

        // Set step to 2
        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('step');
        $property->setValue($command, 2);

        $result = $command->run();

        $this->assertEquals(0, $result);
        $this->assertCount(2, $tracker->migratedFiles);
        $this->assertContains($migration1, $tracker->migratedFiles);
        $this->assertContains($migration2, $tracker->migratedFiles);
        $this->assertNotContains($migration3, $tracker->migratedFiles);
    }

    public function testMigrateOnlyRunsNewMigrations(): void
    {
        $tracker = new \stdClass();
        $tracker->migratedFiles = [];

        // Create stub repository that returns one migration as already ran
        $repository = new class($tracker) extends MigrationRepository {
            public function __construct(private \stdClass $tracker) {}
            public function createMigrationsTable(): void {}
            public function getRan(): array { return ['2024_01_01_000000_create_users']; }
            public function log(string $migration): void {
                $this->tracker->migratedFiles[] = $migration;
            }
            protected function queryBuilder(): never { throw new \Exception('Should not be called'); }
        };

        $output = $this->createMock(Output::class);
        // Only the new migration should be reported
        $output->expects($this->once())
            ->method('info')
            ->with('Migrated: 2024_01_02_000000_create_posts');

        $container = $this->createStub(Container::class);
        $container->method('get')->willReturn($output);

        // Create two migrations, but one is already ran
        $migration1 = '2024_01_01_000000_create_users';
        $migration2 = '2024_01_02_000000_create_posts';

        foreach ([$migration1, $migration2] as $name) {
            $this->createMigrationFile($name, <<<'PHP'
return new class extends \Larafony\Framework\Database\Base\Migrations\Migration {
    public function up(): void {}
    public function down(): void {}
};
PHP);
        }

        $resolver = new MigrationResolver($this->migrationPath);
        $executor = new MigrationExecutor($resolver, $repository);

        $command = new Migrate($container, $repository, $resolver, $executor);
        $result = $command->run();

        $this->assertEquals(0, $result);
        // Only the new migration should be in the tracker
        $this->assertCount(1, $tracker->migratedFiles);
        $this->assertContains($migration2, $tracker->migratedFiles);
        $this->assertNotContains($migration1, $tracker->migratedFiles);
    }

    private function createMigrationFile(string $name, string $content): void
    {
        $path = $this->migrationPath . '/' . $name . '.php';
        file_put_contents($path, "<?php\n\n" . $content);
    }
}
