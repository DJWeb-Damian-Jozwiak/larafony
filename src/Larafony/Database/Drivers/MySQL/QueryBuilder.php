<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL;

use Closure;
use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use Larafony\Framework\Database\Base\Query\Enums\JoinType;
use Larafony\Framework\Database\Base\Query\Enums\LogicalOperator;
use Larafony\Framework\Database\Base\Query\Enums\OrderDirection;
use Larafony\Framework\Database\Base\Query\Enums\QueryType;
use Larafony\Framework\Database\Base\Query\QueryDefinition;
use Larafony\Framework\Database\Drivers\MySQL\Query\Clauses\JoinClause;
use Larafony\Framework\Database\Drivers\MySQL\Query\Clauses\LimitClause;
use Larafony\Framework\Database\Drivers\MySQL\Query\Clauses\OrderByClause;
use Larafony\Framework\Database\Drivers\MySQL\Query\Clauses\Where\WhereBasic;
use Larafony\Framework\Database\Drivers\MySQL\Query\Clauses\Where\WhereBetween;
use Larafony\Framework\Database\Drivers\MySQL\Query\Clauses\Where\WhereIn;
use Larafony\Framework\Database\Drivers\MySQL\Query\Clauses\Where\WhereLike;
use Larafony\Framework\Database\Drivers\MySQL\Query\Clauses\Where\WhereNested;
use Larafony\Framework\Database\Drivers\MySQL\Query\Clauses\Where\WhereNull;
use Larafony\Framework\Database\Drivers\MySQL\Query\Grammar;

/**
 * MySQL Query Builder - fluent API for building SQL queries
 * Implements the concrete SQL building logic
 */
class QueryBuilder extends \Larafony\Framework\Database\Base\Query\QueryBuilder
{
    public function __construct(ConnectionContract $connection)
    {
        parent::__construct($connection);
        $this->grammar = new Grammar();
    }

    public function table(string $table): static
    {
        $this->query = new QueryDefinition($table);
        return $this;
    }

    /**
     * @param string|array<int, string> $columns
     */
    public function select(string|array $columns): static
    {
        $this->query->columns = is_array($columns) ? $columns : func_get_args();
        $this->query->type = QueryType::SELECT;
        return $this;
    }

    public function where(string|Closure $column, mixed $operator = null, mixed $value = null): static
    {
        // Handle closure for nested wheres
        if ($column instanceof Closure) {
            return $this->whereNested($column, LogicalOperator::AND);
        }

        // Handle two-argument where (column, value) - assume =
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->wheres[] = new WhereBasic($column, $operator, $value, LogicalOperator::AND);
        return $this;
    }

    public function orWhere(string|Closure $column, mixed $operator = null, mixed $value = null): static
    {
        // Handle closure for nested wheres
        if ($column instanceof Closure) {
            return $this->whereNested($column, LogicalOperator::OR);
        }

        // Handle two-argument where (column, value) - assume =
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->wheres[] = new WhereBasic($column, $operator, $value, LogicalOperator::OR);
        return $this;
    }

    /**
     * @param array<int, mixed> $values
     */
    public function whereIn(string $column, array $values): static
    {
        $this->query->wheres[] = new WhereIn($column, $values, LogicalOperator::AND, not: false);
        return $this;
    }

    /**
     * @param array<int, mixed> $values
     */
    public function whereNotIn(string $column, array $values): static
    {
        $this->query->wheres[] = new WhereIn($column, $values, LogicalOperator::AND, not: true);
        return $this;
    }

    public function whereNull(string $column): static
    {
        $this->query->wheres[] = new WhereNull($column, LogicalOperator::AND, not: false);
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->query->wheres[] = new WhereNull($column, LogicalOperator::AND, not: true);
        return $this;
    }

    /**
     * @param array<int, mixed> $values
     */
    public function whereBetween(string $column, array $values): static
    {
        $this->query->wheres[] = new WhereBetween($column, $values, LogicalOperator::AND, not: false);
        return $this;
    }

    public function whereLike(string $column, string $pattern): static
    {
        $this->query->wheres[] = new WhereLike($column, $pattern, LogicalOperator::AND);
        return $this;
    }

    public function join(
        string $table,
        string|Closure $first,
        ?string $operator = null,
        ?string $second = null,
        JoinType $type = JoinType::INNER,
    ): static {
        $join = new JoinClause($type, $table);

        if ($first instanceof Closure) {
            $first($join);
        } else {
            $join->on($first, $operator, $second);
        }

        $this->query->joins[] = $join;
        return $this;
    }

    public function leftJoin(
        string $table,
        string|Closure $first,
        ?string $operator = null,
        ?string $second = null,
    ): static {
        return $this->join($table, $first, $operator, $second, JoinType::LEFT);
    }

    public function rightJoin(
        string $table,
        string|Closure $first,
        ?string $operator = null,
        ?string $second = null,
    ): static {
        return $this->join($table, $first, $operator, $second, JoinType::RIGHT);
    }

    public function orderBy(string $column, OrderDirection $direction = OrderDirection::ASC): static
    {
        $this->query->orders[] = new OrderByClause($column, $direction);
        return $this;
    }

    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, OrderDirection::DESC);
    }

    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, OrderDirection::ASC);
    }

    public function limit(int $value): static
    {
        $offset = $this->query->limit?->offset;
        $this->query->limit = new LimitClause($value, $offset);
        return $this;
    }

    public function offset(int $value): static
    {
        $limit = $this->query->limit?->limit;
        $this->query->limit = new LimitClause($limit, $value);
        return $this;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        $sql = $this->grammar->compileSelect($this->query);
        $bindings = $this->query->getBindings();
        $statement = $this->connection->query($sql, $bindings);
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $result = $this->limit(1)->get();
        return $result[0] ?? null;
    }

    public function count(string $column = '*'): int
    {
        $previousColumns = $this->query->columns;
        $this->query->columns = ["COUNT({$column}) as aggregate"];

        $result = $this->first();
        $this->query->columns = $previousColumns;

        return (int) ($result['aggregate'] ?? 0);
    }

    /**
     * @param array<string, mixed> $values
     */
    public function insert(array $values): bool
    {
        $this->query->type = QueryType::INSERT;
        $this->query->values = $values;

        $sql = $this->grammar->compileInsert($this->query);
        $this->connection->query($sql, array_values($values));

        return true;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function insertGetId(array $values): string
    {
        $this->insert($values);
        return $this->connection->getLastInsertId();
    }

    /**
     * @param array<string, mixed> $values
     */
    public function update(array $values): int
    {
        $this->query->type = QueryType::UPDATE;
        $this->query->values = $values;

        $sql = $this->grammar->compileUpdate($this->query);
        $bindings = $this->query->getBindings();

        return $this->connection->query($sql, $bindings)->rowCount();
    }

    public function delete(): int
    {
        $this->query->type = QueryType::DELETE;

        $sql = $this->grammar->compileDelete($this->query);
        $bindings = $this->query->getBindings();

        return $this->connection->query($sql, $bindings)->rowCount();
    }

    public function toSql(): string
    {
        return match ($this->query->type) {
            QueryType::SELECT => $this->grammar->compileSelect($this->query),
            QueryType::INSERT => $this->grammar->compileInsert($this->query),
            QueryType::UPDATE => $this->grammar->compileUpdate($this->query),
            QueryType::DELETE => $this->grammar->compileDelete($this->query),
        };
    }

    protected function whereNested(Closure $callback, LogicalOperator $boolean): static
    {
        $nested = new static($this->connection);
        $nested->query = new QueryDefinition($this->query->table);
        $callback($nested);

        if ($nested->query->wheres) {
            $this->query->wheres[] = new WhereNested($nested->query->wheres, $boolean);
        }

        return $this;
    }
}
