<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Relations;

use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Relation;

class HasMany extends Relation
{
    public function addConstraints(): void
    {
        $this->query->where(
            $this->foreign_key,
            '=',
            $this->parent->{$this->local_key}
        );
    }

    /**
     * @return array<int, Model>|Model
     */
    public function getRelated(): array|Model
    {
        $results = $this->getResults();
        return array_map(
            fn (array $result) => new $this->related()->fill($result),
            $results
        );
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return DB::table($this->related::getTable());
    }
}
