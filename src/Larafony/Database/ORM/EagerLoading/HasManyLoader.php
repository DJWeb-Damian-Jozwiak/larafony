<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\EagerLoading;

use Larafony\Framework\Database\ORM\Contracts\RelationContract;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\QueryBuilders\ModelQueryBuilder;
use Larafony\Framework\Database\ORM\Relations\HasMany;

class HasManyLoader extends BaseRelationLoader
{
    public function load(array $models, string $relationName, RelationContract|HasMany $relation, array $nested): void
    {
        $this->initReflection($relation);

        $foreignKey = $this->getPropertyValue($relation, 'foreign_key');
        $localKey = $this->getPropertyValue($relation, 'local_key');
        $relatedClass = $this->getPropertyValue($relation, 'related');

        $localKeyValues = $this->collectKeyValues($models, $localKey);

        if ($localKeyValues === []) {
            return;
        }

        $relatedModels = $this->loadRelatedModels($relatedClass, $foreignKey, $localKeyValues, $nested);
        $dictionary = $this->groupModelsBy($relatedModels, $foreignKey);

        $this->matchModels($models, $relationName, $dictionary, $localKey);
    }

    /**
     * @param class-string<Model> $relatedClass
     * @param array<mixed> $keyValues
     * @param array<string> $nested
     *
     * @return array<int, Model>
     */
    private function loadRelatedModels(string $relatedClass, string $foreignKey, array $keyValues, array $nested): array
    {
        /** @var ModelQueryBuilder $query */
        $query = (new $relatedClass())->query_builder->whereIn($foreignKey, array_unique($keyValues));

        return $this->buildQueryWithNested($query, $nested)->get();
    }

    /**
     * @param array<int, Model> $models
     * @param array<mixed, array<int, Model>> $dictionary
     */
    private function matchModels(array $models, string $relationName, array $dictionary, string $localKey): void
    {
        foreach ($models as $model) {
            $lkValue = $model->$localKey ?? null;
            $related = $lkValue !== null ? ($dictionary[$lkValue] ?? []) : [];
            $this->assignRelationToModel($model, $relationName, $related);
        }
    }
}
