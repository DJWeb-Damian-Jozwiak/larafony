<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM;

use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\ORM\Contracts\RelationContract;

abstract class Relation implements RelationContract
{
    protected QueryBuilder $query;

    /**
     * @param Model $parent
     * @param class-string<Model> $related
     * @param string $foreign_key
     * @param string $local_key
     */
    public function __construct(
        protected Model $parent,
        protected string $related,
        protected string $foreign_key,
        protected string $local_key,
    ) {
        $this->query = $this->createQueryBuilder();
    }

    abstract public function addConstraints(): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getResults(): array
    {
        return $this->query->get();
    }
}
