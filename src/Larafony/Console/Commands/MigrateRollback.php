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

#[AsCommand(name: 'migrate:rollback')]
class MigrateRollback extends Command
{
    #[CommandOption(name: 'database', description: 'Baza danych do rollback')]
    protected ?string $database = null;

    #[CommandOption(name: 'force', description: 'Wymuś rollback w środowisku produkcyjnym')]
    protected bool $force = false;

    #[CommandOption(name: 'step', description: 'Liczba batchy do cofnięcia')]
    protected int $step = 1;

    public function __construct(
        ContainerContract $container,
        private MigrationRepository $repository,
        private MigrationExecutor $executor,
    ) {
        $output = $container->get(OutputContract::class);
        parent::__construct($output, $container);
    }

    public function run(): int
    {
        $this->repository->createMigrationsTable();

        $migrationsToRollback = $this->getMigrationsToRollback();

        if (! $migrationsToRollback) {
            $this->output->info('Nothing to rollback');
            return 0;
        }

        $this->rollbackMigrations($migrationsToRollback);

        return 0;
    }

    /**
     * @return array<int, string>
     */
    private function getMigrationsToRollback(): array
    {
        $lastBatch = $this->repository->getLastBatchNumber();

        $migrations = [];
        $steps = $this->step;

        for ($i = 0; $i < $steps; $i++) {
            $batchNumber = $lastBatch - $i;
            $batchMigrations = $this->repository->getMigrationsByBatch($batchNumber);
            $migrations = array_merge($migrations, $batchMigrations);
        }

        // Odwracamy kolejność, żeby cofać od najnowszych
        return array_reverse($migrations);
    }

    /**
     * @param array<int, string> $migrations
     *
     * @return void
     */
    private function rollbackMigrations(array $migrations): void
    {
        $rolledBack = $this->executor->executeMigrations(
            $migrations,
            'down',
        );

        foreach ($rolledBack as $migration) {
            $this->output->info("Rolled back: {$migration}");
        }
    }
}
