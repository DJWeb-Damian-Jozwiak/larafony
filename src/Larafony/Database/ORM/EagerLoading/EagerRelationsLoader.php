<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\EagerLoading;

use Larafony\Framework\Database\ORM\Contracts\RelationContract;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Relations\BelongsTo;
use Larafony\Framework\Database\ORM\Relations\BelongsToMany;
use Larafony\Framework\Database\ORM\Relations\HasMany;
use Larafony\Framework\Database\ORM\Relations\HasManyThrough;
use Larafony\Framework\Database\ORM\Relations\HasOne;

class EagerRelationsLoader
{
    /**
     * Eager load relations for given models
     *
     * @param array<int, Model> $models
     * @param array<string, array<string>> $eagerLoad
     */
    public function load(array $models, array $eagerLoad): void
    {
        foreach ($eagerLoad as $relationName => $nested) {
            $this->loadRelation($models, $relationName, $nested);
        }
    }

    /**
     * Load a single relation for given models
     *
     * @param array<int, Model> $models
     * @param string $relationName
     * @param array<string> $nested
     */
    protected function loadRelation(array $models, string $relationName, array $nested): void
    {
        if ($models === []) {
            return;
        }

        // Get the relation instance from the first model
        $firstModel = $models[0];
        $relationInstance = $firstModel->relations->getRelationInstance($relationName);

        // Delegate to specific loader based on relation type
        $loader = $this->getLoaderForRelation($relationInstance);
        $loader->load($models, $relationName, $relationInstance, $nested);
    }

    /**
     * Get appropriate loader for relation type
     */
    protected function getLoaderForRelation(RelationContract $relation): RelationLoaderContract
    {
        return match (true) {
            $relation instanceof BelongsTo => new BelongsToLoader(),
            $relation instanceof BelongsToMany => new BelongsToManyLoader(),
            $relation instanceof HasManyThrough => new HasManyThroughLoader(),
            $relation instanceof HasMany => new HasManyLoader(),
            $relation instanceof HasOne => new HasOneLoader(),
            default => throw new \RuntimeException('Unsupported relation type: ' . $relation::class),
        };
    }
}
