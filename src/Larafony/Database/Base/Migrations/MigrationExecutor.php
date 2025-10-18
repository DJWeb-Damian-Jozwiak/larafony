<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Migrations;

final readonly class MigrationExecutor
{
    public function __construct(
        private MigrationResolver $resolver,
        private MigrationRepository $repository,
    ) {
    }

    /**
     * @param array<int, string> $migrations
     *
     * @return array<int, string>
     */
    public function executeMigrations(
        array $migrations,
        string $direction,
    ): array {
        $method = $direction === 'up' ? $this->runUp(...) : $this->runDown(...);
        $executed = [];

        foreach ($migrations as $migration) {
            $method($migration);
            $executed[] = $migration;
        }

        return $executed;
    }

    private function runUp(string $file): void
    {
        $migration = $this->resolver->resolve($file);
        $migration->up();
        $this->repository->log($migration->name);
    }

    private function runDown(string $file): void
    {
        $migration = $this->resolver->resolve($file);
        $migration->down();
        $this->repository->delete($file);
    }
}
