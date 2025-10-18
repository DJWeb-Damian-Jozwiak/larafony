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
}
