<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Contracts;

interface ConnectionContract
{
    public function connect(): void;

    /**
     * @param array<string|int, string|float|int|null> $params
     */
    public function query(string $sql, array $params = []): \PDOStatement;

    /**
     * @return array<int, int|false>
     */
    public function getConnectionOptions(): array;

    public function getLastInsertId(): ?string;

    public function quote(int|float|string|bool|null $value): string;

    /**
     * Execute a SELECT query and return all results
     * Cursor is automatically closed after fetching
     *
     * @param string $sql
     * @param array<int, mixed> $params
     *
     * @return array<int, array<string, mixed>>
     */
    public function select(string $sql, array $params = []): array;

    /**
     * Execute an INSERT/UPDATE/DELETE query and return affected rows
     * Cursor is automatically closed after getting row count
     *
     * @param string $sql
     * @param array<int, mixed> $params
     *
     * @return int Number of affected rows
     */
    public function execute(string $sql, array $params = []): int;
}
