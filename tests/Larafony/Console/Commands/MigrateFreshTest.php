<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Larafony\Console\Commands;

use Larafony\Framework\Console\Commands\MigrateFresh;
use Larafony\Framework\Console\Output;
use Larafony\Framework\Container\Container;
use Larafony\Framework\Database\Base\Migrations\MigrationExecutor;
use Larafony\Framework\Database\Base\Migrations\MigrationResolver;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\Drivers\MySQL\Migrations\MigrationRepository;
use Larafony\Framework\Database\Drivers\MySQL\Schema\DatabaseInfo;
use Larafony\Framework\Database\Schema;
use PHPUnit\Framework\TestCase;

final class MigrateFreshTest extends TestCase
{
    private string $migrationPath;

    protected function setUp(): void
    {
        // Create temp migration directory
        $this->migrationPath = sys_get_temp_dir() . '/test_migrations_fresh_' . uniqid();
        mkdir($this->migrationPath, 0777, true);

        // Setup Schema facade with mock manager
        $manager = $this->createMock(DatabaseManager::class);
        Schema::withManager($manager);
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

    public function testFreshWithNoTablesAndNoMigrations(): void
    {
        // Create stub repository and database info
        $repository = new class extends MigrationRepository {
            public function __construct() {}
            public function createMigrationsTable(): void {}
            public function log(string $migration): void {}
            protected function queryBuilder(): never { throw new \Exception('Should not be called'); }
        };

        $databaseInfo = new class extends DatabaseInfo {
            public function __construct() {}
            public function getTables(): array { return []; }
        };

        $output = $this->createMock(Output::class);
        $output->expects($this->once())
            ->method('info')
            ->willReturnCallback(function (string $message) {
                static $callCount = 0;
                $callCount++;
                $this->assertEquals('Nothing to migrate', $message);
            });

        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($output);

        $resolver = new MigrationResolver($this->migrationPath);
        $executor = new MigrationExecutor($resolver, $repository);

        $command = new MigrateFresh(
            $container,
            $repository,
            $resolver,
            $executor,
            $databaseInfo
        );

        $result = $command->run();
        $this->assertEquals(0, $result);
    }

    public function testFreshDropsExistingTablesAndRunsMigrations(): void
    {
        $tracker = new \stdClass();
        $tracker->migratedFiles = [];

        // Create stub repository
        $repository = new class($tracker) extends MigrationRepository {
            public function __construct(private \stdClass $tracker) {}
            public function createMigrationsTable(): void {}
            public function log(string $migration): void {
                $this->tracker->migratedFiles[] = $migration;
            }
            protected function queryBuilder(): never { throw new \Exception('Should not be called'); }
        };

        // Database info that returns existing tables
        $databaseInfo = new class extends DatabaseInfo {
            public function __construct() {}
            public function getTables(): array { return ['old_table1', 'old_table2']; }
        };

        // Create a new migration
        $migrationName = '2024_01_01_000000_create_new_table';
        $this->createMigrationFile($migrationName, <<<'PHP'
return new class extends \Larafony\Framework\Database\Base\Migrations\Migration {
    public function up(): void {
        // Migration logic
    }
    public function down(): void {}
};
PHP);

        $output = $this->createMock(Output::class);
        // Expect 2 "Dropped" + 1 "Migrated" = 3 messages
        $output->expects($this->exactly(3))->method('info');

        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($output);

        $resolver = new MigrationResolver($this->migrationPath);
        $executor = new MigrationExecutor($resolver, $repository);

        $command = new MigrateFresh(
            $container,
            $repository,
            $resolver,
            $executor,
            $databaseInfo
        );

        $result = $command->run();

        $this->assertEquals(0, $result);
        $this->assertContains($migrationName, $tracker->migratedFiles);
    }

    public function testFreshWithMultipleMigrations(): void
    {
        $tracker = new \stdClass();
        $tracker->migratedFiles = [];

        $repository = new class($tracker) extends MigrationRepository {
            public function __construct(private \stdClass $tracker) {}
            public function createMigrationsTable(): void {}
            public function log(string $migration): void {
                $this->tracker->migratedFiles[] = $migration;
            }
            protected function queryBuilder(): never { throw new \Exception('Should not be called'); }
        };

        $databaseInfo = new class extends DatabaseInfo {
            public function __construct() {}
            public function getTables(): array { return ['old_table']; }
        };

        // Create two migrations
        $migration1 = '2024_01_01_000000_create_users';
        $migration2 = '2024_01_02_000000_create_posts';

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
        // Expect: 1 "Dropped" + 2 "Migrated" = 3 messages
        $output->expects($this->exactly(3))->method('info');

        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($output);

        $resolver = new MigrationResolver($this->migrationPath);
        $executor = new MigrationExecutor($resolver, $repository);

        $command = new MigrateFresh(
            $container,
            $repository,
            $resolver,
            $executor,
            $databaseInfo
        );

        $result = $command->run();

        $this->assertEquals(0, $result);
        $this->assertCount(2, $tracker->migratedFiles);
        $this->assertContains($migration1, $tracker->migratedFiles);
        $this->assertContains($migration2, $tracker->migratedFiles);
    }

    private function createMigrationFile(string $name, string $content): void
    {
        $path = $this->migrationPath . '/' . $name . '.php';
        file_put_contents($path, "<?php\n\n" . $content);
    }
}
