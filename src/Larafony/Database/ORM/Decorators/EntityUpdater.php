<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Decorators;

use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;

class EntityUpdater
{
    public function __construct(private Model $model)
    {
    }

    public function update(): void
    {
        $queryBuilder = DB::table($this->model->table);
        $changedProperties = $this->model->observer->getChangedProperties();

        $queryBuilder
            ->where($this->model->primary_key_name, '=', $this->model->id)
            ->update($changedProperties);
    }
}
