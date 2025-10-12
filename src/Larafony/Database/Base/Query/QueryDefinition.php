<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Query;

use Larafony\Framework\Database\Base\Query\Clauses\JoinClause;
use Larafony\Framework\Database\Base\Query\Clauses\LimitClause;
use Larafony\Framework\Database\Base\Query\Clauses\OrderByClause;
use Larafony\Framework\Database\Base\Query\Clauses\Where\WhereClause;
use Larafony\Framework\Database\Base\Query\Enums\QueryType;

/**
 * Query state holder - stores all components of a query
 * Similar to TableDefinition in Schema
 */
class QueryDefinition
{
    public QueryType $type = QueryType::SELECT;

    /**
     * @var array<int, string>
     */
    public array $columns = ['*'];

    /**
     * @var array<int, WhereClause>
     */
    public array $wheres = [];

    /**
     * @var array<int, JoinClause>
     */
    public array $joins = [];

    /**
     * @var array<int, OrderByClause>
     */
    public array $orders = [];

    /**
     * @var array<string, string>
     */
    public array $groups = [];

    public ?LimitClause $limit = null;

    /**
     * @var array<string, mixed>
     */
    public array $values = [];

    public function __construct(public readonly string $table)
    {
    }

    /**
     * Get all bindings for prepared statements
     *
     * @return array<int, mixed>
     */
    public function getBindings(): array
    {
        $bindings = [];

        // UPDATE bindings come first
        if ($this->type === QueryType::UPDATE) {
            $bindings = array_merge($bindings, array_values($this->values));
        }

        // WHERE bindings
        foreach ($this->wheres as $where) {
            $bindings = array_merge($bindings, $where->getBindings());
        }

        return $bindings;
    }
}
