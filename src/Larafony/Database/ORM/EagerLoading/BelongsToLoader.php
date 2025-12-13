<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\EagerLoading;

use Larafony\Framework\Database\ORM\Contracts\RelationContract;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\QueryBuilders\ModelQueryBuilder;
use Larafony\Framework\Database\ORM\Relations\BelongsTo;

class BelongsToLoader extends BaseRelationLoader
{
    public function load(
        array $models,
        string $relationName,
        RelationContract|BelongsTo $relation,
        array $nested
    ): void {
        $this->initReflection($relation);

        $foreignKey = $this->getPropertyValue($relation, 'foreign_key');
        $localKey = $this->getPropertyValue($relation, 'local_key');
        $relatedClass = $this->getPropertyValue($relation, 'related');

        $foreignKeyValues = $this->collectKeyValues($models, $foreignKey);

        if ($foreignKeyValues === []) {
            return;
        }

        $relatedModels = $this->loadRelatedModels($relatedClass, $localKey, $foreignKeyValues, $nested);
        $dictionary = $this->indexModelsBy($relatedModels, $localKey);

        $this->matchModels($models, $relationName, $dictionary, $foreignKey);
    }

    /**
     * @param class-string<Model> $relatedClass
     * @param array<mixed> $keyValues
     * @param array<string> $nested
     *
     * @return array<int, Model>
     */
    private function loadRelatedModels(string $relatedClass, string $localKey, array $keyValues, array $nested): array
    {
        /** @var ModelQueryBuilder $query */
        $query = new $relatedClass()->query_builder->whereIn($localKey, array_unique($keyValues));

        return $this->buildQueryWithNested($query, $nested)->get();
    }

    /**
     * @param array<int, Model> $models
     * @param array<mixed, Model> $dictionary
     */
    private function matchModels(array $models, string $relationName, array $dictionary, string $foreignKey): void
    {
        foreach ($models as $model) {
            $fkValue = $model->$foreignKey ?? null;
            $relatedModel = $fkValue !== null ? ($dictionary[$fkValue] ?? null) : null;
            $this->assignRelationToModel($model, $relationName, $relatedModel);
        }
    }
}
