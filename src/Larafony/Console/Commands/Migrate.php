<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Attributes\CommandOption;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Database\Base\Migrations\MigrationExecutor;
use Larafony\Framework\Database\Base\Migrations\MigrationRepository;
use Larafony\Framework\Database\Base\Migrations\MigrationResolver;

#[AsCommand(name: 'migrate')]
class Migrate extends Command
{
    #[CommandOption(name: 'database', description: 'Baza danych do migracji')]
    protected ?string $database = null;

    #[CommandOption(name: 'force', description: 'Wymuś migrację w środowisku produkcyjnym')]
    protected bool $force = false;

    #[CommandOption(name: 'step', description: 'Liczba migracji do wykonania')]
    protected ?int $step = null;

    public function __construct(
        ContainerContract $container,
        private MigrationRepository $repository,
        private MigrationResolver $resolver,
        private MigrationExecutor $executor,
    ) {
        $output = $container->get(OutputContract::class);
        parent::__construct($output, $container);
    }

    public function withMigrationRepository(MigrationRepository $repository): void
    {
        $this->repository = $repository;
    }

    public function withMigrationResolver(MigrationResolver $resolver): void
    {
        $this->resolver = $resolver;
    }

    public function withMigrationExecutor(MigrationExecutor $executor): void
    {
        $this->executor = $executor;
    }

    public function run(): int
    {
        $this->repository->createMigrationsTable();

        $migrations = $this->getPendingMigrations();

        if (! $migrations) {
            $this->output->info('Nothing to migrate');
            return 0;
        }

        $batches = array_slice($migrations, 0, $this->step);

        $this->runMigrationBatch($batches);

        return 0;
    }

    /**
     * @return array<int, string>
     */
    private function getPendingMigrations(): array
    {
        $files = $this->resolver->getMigrationFiles();
        $ran = $this->repository->getRan();

        return array_diff($files, $ran);
    }

    /**
     * @param array<int, string> $batch
     *
     * @return void
     */
    private function runMigrationBatch(array $batch): void
    {
        $migrations = $this->executor->executeMigrations(
            $batch,
            'up',
        );

        foreach ($migrations as $migration) {
            $this->output->info("Migrated: {$migration}");
        }
    }
}
