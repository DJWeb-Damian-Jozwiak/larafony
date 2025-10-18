<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Database\Base\Migrations\MigrationExecutor;
use Larafony\Framework\Database\Base\Migrations\MigrationRepository;
use Larafony\Framework\Database\Base\Migrations\MigrationResolver;
use Larafony\Framework\Database\Drivers\MySQL\Schema\DatabaseInfo;
use Larafony\Framework\Database\Schema;

#[AsCommand(name: 'migrate:fresh')]
class MigrateFresh extends Command
{
    public function __construct(
        ContainerContract $container,
        private MigrationRepository $repository,
        private MigrationResolver $resolver,
        private MigrationExecutor $executor,
        private DatabaseInfo $databaseInfo,
    ) {
        $output = $container->get(OutputContract::class);
        parent::__construct($output, $container);
    }

    public function run(): int
    {
        $this->dropAllTables();
        $this->runMigrations();

        return 0;
    }

    private function dropAllTables(): void
    {
        $tables = $this->databaseInfo->getTables();
        foreach ($tables as $table) {
            $sql = Schema::drop($table);
            Schema::execute($sql);
            $this->output->info("Dropped: {$table}");
        }
    }

    private function runMigrations(): void
    {
        $this->repository->createMigrationsTable();

        $migrations = $this->resolver->getMigrationFiles();

        if (! $migrations) {
            $this->output->info('Nothing to migrate');
            return;
        }

        $migrated = $this->executor->executeMigrations($migrations, 'up');

        foreach ($migrated as $migration) {
            $this->output->info("Migrated: {$migration}");
        }
    }
}
