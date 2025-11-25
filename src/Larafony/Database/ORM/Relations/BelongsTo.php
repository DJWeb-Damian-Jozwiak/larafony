<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Relations;

use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Relation;

class BelongsTo extends Relation
{
    public function addConstraints(): void
    {
        $this->query->where(
            $this->local_key,
            '=',
            $this->parent->{$this->foreign_key}
        );
    }

    /**
     * @return array<int, Model>|Model|null
     */
    public function getRelated(): array|Model|null
    {
        $result = $this->getResults();
        if (! $result) {
            return null;
        }
        return new $this->related()->fill($result[0]);
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return DB::table($this->related::getTable());
    }
}
