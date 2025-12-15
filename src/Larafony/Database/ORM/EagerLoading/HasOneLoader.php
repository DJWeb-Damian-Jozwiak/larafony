<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\EagerLoading;

use Larafony\Framework\Database\ORM\Contracts\RelationContract;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\QueryBuilders\ModelQueryBuilder;
use Larafony\Framework\Database\ORM\Relations\HasOne;

class HasOneLoader extends BaseRelationLoader
{
    public function load(array $models, string $relationName, RelationContract|HasOne $relation, array $nested): void
    {
        $this->initReflection($relation);

        $foreignKey = $this->getPropertyValue($relation, 'foreign_key');
        $localKey = $this->getPropertyValue($relation, 'local_key');
        $relatedClass = $this->getPropertyValue($relation, 'related');

        $localKeyValues = $this->collectKeyValues($models, $localKey);

        if ($localKeyValues === []) {
            return;
        }

        $relatedModel = $this->loadRelatedModel($relatedClass, $foreignKey, $localKeyValues, $nested);

        $this->matchModels($models, $relationName, $relatedModel);
    }

    /**
     * @param class-string<Model> $relatedClass
     * @param array<mixed> $keyValues
     * @param array<string> $nested
     *
     * @return Model|null
     */
    private function loadRelatedModel(string $relatedClass, string $foreignKey, array $keyValues, array $nested): ?Model
    {
        /** @var ModelQueryBuilder $query */
        $query = (new $relatedClass())->query_builder->whereIn($foreignKey, array_unique($keyValues));

        return $this->buildQueryWithNested($query, $nested)->first();
    }

    /**
     * @param array<int, Model> $models
     */
    private function matchModels(array $models, string $relationName, ?Model $relatedModel): void
    {
        $this->assignRelationToModel(array_first($models), $relationName, $relatedModel);
    }
}
