<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\QueryBuilders;

use Closure;
use Larafony\Framework\Database\Base\Query\Enums\JoinType;
use Larafony\Framework\Database\Base\Query\Enums\OrderDirection;
use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\EagerLoading\EagerRelationsLoader;
use Larafony\Framework\Database\ORM\Model;

class ModelQueryBuilder
{
    protected QueryBuilder $builder;

    /**
     * @var array<string, array<string>>
     */
    protected array $eagerLoad = [];

    public function __construct(public readonly Model $model)
    {
        $this->builder = DB::table($this->model->table);
    }

    public function where(string $column, string $operator, mixed $value): static
    {
        $this->builder->where($column, $operator, $value);
        return $this;
    }

    public function orWhere(string $column, string $operator, mixed $value): static
    {
        $this->builder->orWhere($column, $operator, $value);
        return $this;
    }

    /**
     * @param array<int, mixed> $values
     */
    public function whereIn(string $column, array $values): static
    {
        $this->builder->whereIn($column, $values);
        return $this;
    }

    /**
     * @param array<int, mixed> $values
     */
    public function whereNotIn(string $column, array $values): static
    {
        $this->builder->whereNotIn($column, $values);
        return $this;
    }

    public function whereNull(string $column): static
    {
        $this->builder->whereNull($column);
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->builder->whereNotNull($column);
        return $this;
    }

    /**
     * @param array<int, mixed> $values
     */
    public function whereBetween(string $column, array $values): static
    {
        $this->builder->whereBetween($column, $values);
        return $this;
    }

    public function whereLike(string $column, string $pattern): static
    {
        $this->builder->whereLike($column, $pattern);
        return $this;
    }

    public function whereNested(Closure $callback, string $boolean = 'and'): static
    {
        $this->builder->whereNested($callback, $boolean);
        return $this;
    }

    public function join(
        string $table,
        string|Closure $first,
        ?string $operator = null,
        ?string $second = null,
        JoinType $type = JoinType::INNER,
    ): static {
        $this->builder->join($table, $first, $operator, $second, $type);
        return $this;
    }

    public function leftJoin(
        string $table,
        string|Closure $first,
        ?string $operator = null,
        ?string $second = null,
    ): static {
        $this->builder->leftJoin($table, $first, $operator, $second);
        return $this;
    }

    public function rightJoin(
        string $table,
        string|Closure $first,
        ?string $operator = null,
        ?string $second = null,
    ): static {
        $this->builder->rightJoin($table, $first, $operator, $second);
        return $this;
    }

    public function orderBy(string $column, OrderDirection $direction = OrderDirection::ASC): static
    {
        $this->builder->orderBy($column, $direction);
        return $this;
    }

    public function latest(string $column = 'created_at'): static
    {
        $this->builder->latest($column);
        return $this;
    }

    public function oldest(string $column = 'created_at'): static
    {
        $this->builder->oldest($column);
        return $this;
    }

    public function limit(int $value): static
    {
        $this->builder->limit($value);
        return $this;
    }

    public function offset(int $value): static
    {
        $this->builder->offset($value);
        return $this;
    }

    /**
     * @param array<int, string> $columns
     */
    public function select(array $columns = ['*']): static
    {
        $this->builder->select($columns);
        return $this;
    }

    /**
     * Set relations to be eager loaded
     *
     * @param array<int, string> $relations
     */
    public function with(array $relations): static
    {
        foreach ($relations as $relation) {
            // Support nested relations: 'user.profile'
            $parts = explode('.', $relation);
            $this->eagerLoad[$parts[0]] = array_slice($parts, 1);
        }

        return $this;
    }

    /**
     * @return array<int, Model>
     */
    public function get(): array
    {
        $results = $this->builder->get();
        $models = $this->hydrateMany($results);

        if (!empty($this->eagerLoad)) {
            $loader = new EagerRelationsLoader();
            $loader->load($models, $this->eagerLoad);
        }

        return $models;
    }

    public function first(): ?Model
    {
        $result = $this->builder->first();
        $model = $result ? $this->hydrate($result) : null;

        if ($model !== null && !empty($this->eagerLoad)) {
            $loader = new EagerRelationsLoader();
            $loader->load([$model], $this->eagerLoad);
        }

        return $model;
    }

    public function count(string $column = '*'): int
    {
        return $this->builder->count($column);
    }

    public function delete(): int
    {
        return $this->builder->delete();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function hydrate(array $attributes): Model
    {
        return (clone $this->model)->fill($attributes);
    }

    /**
     * @param array<int, array<string, mixed>> $results
     *
     * @return array<int, Model>
     */
    protected function hydrateMany(array $results): array
    {
        return array_map(fn (array $result) => $this->hydrate($result), $results);
    }
}
