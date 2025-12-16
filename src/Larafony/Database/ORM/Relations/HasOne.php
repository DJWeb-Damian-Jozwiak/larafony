<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Relations;

use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Relation;

class HasOne extends Relation
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
     * @return Model|null
     */
    public function getRelated(): ?Model
    {
        $result = $this->query->first();

        return $result
            ? new $this->related()->fill($result)
            : null;
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return DB::table($this->related::getTable());
    }
}
