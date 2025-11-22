<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Decorators;

use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Web\Application;

class EntityInserter
{
    public function __construct(private Model $model)
    {
    }

    public function insert(): int|string
    {
        $queryBuilder = DB::table($this->model->table);
        $changedProperties = $this->model->observer->getChangedProperties();

        return $queryBuilder->insertGetId($changedProperties);
    }
}
