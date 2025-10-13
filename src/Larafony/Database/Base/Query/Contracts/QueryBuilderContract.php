<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Query\Contracts;

use Closure;
use Larafony\Framework\Database\Base\Query\Enums\JoinType;
use Larafony\Framework\Database\Base\Query\Enums\OrderDirection;

/**
 * Query Builder Contract - fluent interface for building queries
 */
interface QueryBuilderContract
{
    public function table(string $table): static;

    /**
     * @param array<int, string> $columns
     */
    public function select(array $columns): static;

    public function where(string $column, string $operator, mixed $value): static;

    public function orWhere(string $column, string $operator, mixed $value): static;

    /**
     * @param array<int, mixed> $values
     */
    public function whereIn(string $column, array $values): static;
    public function whereNested(Closure $callback, string $boolean): static;

    /**
     * @param array<int, mixed> $values
     */
    public function whereNotIn(string $column, array $values): static;

    public function whereNull(string $column): static;

    public function whereNotNull(string $column): static;

    /**
     * @param array<int, mixed> $values
     */
    public function whereBetween(string $column, array $values): static;

    public function whereLike(string $column, string $pattern): static;

    public function join(
        string $table,
        string|Closure $first,
        ?string $operator = null,
        ?string $second = null,
        JoinType $type = JoinType::INNER,
    ): static;

    public function leftJoin(
        string $table,
        string|Closure $first,
        ?string $operator = null,
        ?string $second = null,
    ): static;

    public function rightJoin(
        string $table,
        string|Closure $first,
        ?string $operator = null,
        ?string $second = null,
    ): static;

    public function orderBy(string $column, OrderDirection $direction = OrderDirection::ASC): static;

    public function latest(string $column = 'created_at'): static;

    public function oldest(string $column = 'created_at'): static;

    public function limit(int $value): static;

    public function offset(int $value): static;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get(): array;

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array;

    public function count(string $column = '*'): int;

    /**
     * @param array<string, mixed> $values
     */
    public function insert(array $values): bool;

    /**
     * @param array<string, mixed> $values
     */
    public function insertGetId(array $values): string;

    /**
     * @param array<string, mixed> $values
     */
    public function update(array $values): int;

    public function delete(): int;

    public function toSql(): string;
}
