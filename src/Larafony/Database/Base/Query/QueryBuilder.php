<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Query;

use Closure;
use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use Larafony\Framework\Database\Base\Query\Contracts\GrammarContract;
use Larafony\Framework\Database\Base\Query\Contracts\QueryBuilderContract;
use Larafony\Framework\Database\Base\Query\Enums\JoinType;
use Larafony\Framework\Database\Base\Query\Enums\OrderDirection;

/**
 * Base Query Builder - provides fluent API
 * Abstract class - no SQL building here, only state management
 * Concrete implementations in drivers (MySQL, PostgreSQL, etc.)
 */
abstract class QueryBuilder implements QueryBuilderContract
{
    protected GrammarContract $grammar;
    protected QueryDefinition $query;

    public function __construct(protected readonly ConnectionContract $connection)
    {
    }

    abstract public function table(string $table): static;

    /**
     * @param array<int, string> $columns
     */
    abstract public function select(array $columns): static;

    abstract public function where(string $column, string $operator, mixed $value): static;

    abstract public function orWhere(string $column, string $operator, mixed $value): static;

    /**
     * @param array<int, mixed> $values
     */
    abstract public function whereIn(string $column, array $values): static;

    /**
     * @param array<int, mixed> $values
     */
    abstract public function whereNotIn(string $column, array $values): static;

    abstract public function whereNull(string $column): static;

    abstract public function whereNotNull(string $column): static;
    abstract public function whereNested(Closure $callback, string $boolean): static;

    /**
     * @param array<int, mixed> $values
     */
    abstract public function whereBetween(string $column, array $values): static;

    abstract public function whereLike(string $column, string $pattern): static;

    abstract public function join(
        string $table,
        string|Closure $first,
        ?string $operator = null,
        ?string $second = null,
        JoinType $type = JoinType::INNER,
    ): static;

    abstract public function leftJoin(
        string $table,
        string|Closure $first,
        ?string $operator = null,
        ?string $second = null,
    ): static;

    abstract public function rightJoin(
        string $table,
        string|Closure $first,
        ?string $operator = null,
        ?string $second = null,
    ): static;

    abstract public function orderBy(string $column, OrderDirection $direction = OrderDirection::ASC): static;

    abstract public function latest(string $column = 'created_at'): static;

    abstract public function oldest(string $column = 'created_at'): static;

    abstract public function limit(int $value): static;

    abstract public function offset(int $value): static;

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract public function get(): array;

    /**
     * @return array<string, mixed>|null
     */
    abstract public function first(): ?array;

    abstract public function count(string $column = '*'): int;

    /**
     * @param array<string, mixed> $values
     */
    abstract public function insert(array $values): bool;

    /**
     * @param array<string, mixed> $values
     */
    abstract public function insertGetId(array $values): string;

    /**
     * @param array<string, mixed> $values
     */
    abstract public function update(array $values): int;

    abstract public function delete(): int;

    abstract public function toSql(): string;
}
