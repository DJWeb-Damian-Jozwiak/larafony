<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\EagerLoading;

use Larafony\Framework\Database\ORM\Contracts\RelationContract;
use Larafony\Framework\Database\ORM\Model;

interface RelationLoaderContract
{
    /**
     * Load relation for given models
     *
     * @param array<int, Model> $models
     * @param string $relationName
     * @param RelationContract $relation
     * @param array<string> $nested
     */
    public function load(array $models, string $relationName, RelationContract $relation, array $nested): void;
}
