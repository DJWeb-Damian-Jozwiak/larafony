<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Migrations;

use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\Base\Schema\TableDefinition;
use Larafony\Framework\Database\Drivers\MySQL\Schema\DatabaseInfo;
use Larafony\Framework\Database\Schema;

abstract class MigrationRepository
{
    private const string MIGRATIONS_TABLE = 'migrations';
    private readonly DatabaseInfo $databaseInfo;

    public function __construct(protected ConnectionContract $connection)
    {
        $this->databaseInfo = new DatabaseInfo($connection);
    }

    public function log(string $migration): void
    {
        $this->queryBuilder()->table(self::MIGRATIONS_TABLE)
            ->insert([
                'migration' => $migration,
                'batch' => $this->getNextBatchNumber(),
            ]);
    }
    public function createMigrationsTable(): void
    {
        $tables = $this->databaseInfo->getTables();
        if (in_array(self::MIGRATIONS_TABLE, $tables)) {
            return;
        }
        $sql = Schema::create(self::MIGRATIONS_TABLE, static function (TableDefinition $table): void {
            $table->id();
            $table->string('migration', 255);
            $table->integer('batch');
        });
        Schema::execute($sql);
    }

    /**
     * @return array<int, array{migration: string, batch: int}>
     */
    public function getMigrations(): array
    {
        return $this->queryBuilder()
            ->table(self::MIGRATIONS_TABLE)
            ->select(['*'])->get();
    }

    public function delete(string $migration): void
    {
        $this->queryBuilder()->table(self::MIGRATIONS_TABLE)
            ->where('migration', '=', $migration)
            ->delete();
    }

    /**
     * @return array<int, string>
     */
    public function getRan(): array
    {
        return array_column($this->getMigrations(), 'migration');
    }

    public function getLastBatchNumber(): int
    {
        return (int) ($this->queryBuilder()->table(self::MIGRATIONS_TABLE)
            ->select([
                'max(batch) as batch',
            ])->first()['batch'] ?? 0);
    }

    /**
     * @return array<int, string>
     */
    public function getMigrationsByBatch(int $batch): array
    {
        $migrations = $this->queryBuilder()
            ->table(self::MIGRATIONS_TABLE)
            ->select(['migration'])
            ->where('batch', '=', $batch)
            ->get();

        return array_column($migrations, 'migration');
    }

    abstract protected function queryBuilder(): QueryBuilder;

    private function getNextBatchNumber(): int
    {
        $current = $this->queryBuilder()->table(self::MIGRATIONS_TABLE)
            ->select([
                'max(batch) as batch',
            ])->first()['batch'] ?? 0;
        return $current + 1;
    }
}
