<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\EagerLoading;

use Larafony\Framework\Database\ORM\Contracts\RelationContract;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\QueryBuilders\ModelQueryBuilder;
use Larafony\Framework\Database\ORM\Relations\BelongsToMany;

class BelongsToManyLoader extends BaseRelationLoader
{
    public function load(array $models, string $relationName, RelationContract|BelongsToMany $relation, array $nested): void
    {
        $this->initReflection($relation);

        $parentIds = $this->collectKeyValues($models, 'id');

        if ($parentIds === []) {
            return;
        }

        $config = $this->extractRelationConfig($relation);
        $pivotData = $this->loadPivotData($config, $parentIds);
        $relatedIds = array_unique(array_column($pivotData, $config['relatedPivotKey']));

        if ($relatedIds === []) {
            $this->assignEmptyRelations($models, $relationName);

            return;
        }

        $relatedModels = $this->loadRelatedModels($config['relatedClass'], $relatedIds, $nested);
        $relatedDictionary = $this->indexModelsBy($relatedModels, 'id');
        $pivotDictionary = $this->buildPivotDictionary($pivotData, $config, $relatedDictionary);

        $this->matchModels($models, $relationName, $pivotDictionary);
    }

    /**
     * @return array{relatedClass: class-string<Model>, pivotTable: string, foreignPivotKey: string, relatedPivotKey: string}
     */
    private function extractRelationConfig(BelongsToMany $relation): array
    {
        /** @var class-string<Model> $relatedClass */
        $relatedClass = $this->getPropertyValue($relation, 'related');

        return [
            'relatedClass' => $relatedClass,
            'pivotTable' => $this->getPropertyValue($relation, 'pivot_table'),
            'foreignPivotKey' => $this->getPropertyValue($relation, 'foreign_pivot_key'),
            'relatedPivotKey' => $this->getPropertyValue($relation, 'related_pivot_key'),
        ];
    }

    /**
     * @param array{relatedClass: class-string<Model>, pivotTable: string, foreignPivotKey: string, relatedPivotKey: string} $config
     * @param array<mixed> $parentIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadPivotData(array $config, array $parentIds): array
    {
        return DB::table($config['pivotTable'])
            ->whereIn($config['foreignPivotKey'], array_unique($parentIds))
            ->get();
    }

    /**
     * @param class-string<Model> $relatedClass
     * @param array<mixed> $relatedIds
     * @param array<string> $nested
     *
     * @return array<int, Model>
     */
    private function loadRelatedModels(string $relatedClass, array $relatedIds, array $nested): array
    {
        /** @var ModelQueryBuilder $query */
        $query = (new $relatedClass())->query_builder->whereIn('id', $relatedIds);

        return $this->buildQueryWithNested($query, $nested)->get();
    }

    /**
     * @param array<int, array<string, mixed>> $pivotData
     * @param array{relatedClass: class-string<Model>, pivotTable: string, foreignPivotKey: string, relatedPivotKey: string} $config
     * @param array<mixed, Model> $relatedDictionary
     *
     * @return array<mixed, array<int, Model>>
     */
    private function buildPivotDictionary(array $pivotData, array $config, array $relatedDictionary): array
    {
        $dictionary = [];
        foreach ($pivotData as $pivot) {
            $parentId = $pivot[$config['foreignPivotKey']];
            $relatedId = $pivot[$config['relatedPivotKey']];
            $dictionary[$parentId] ??= [];

            if (isset($relatedDictionary[$relatedId])) {
                $dictionary[$parentId][] = $relatedDictionary[$relatedId];
            }
        }

        return $dictionary;
    }

    /**
     * @param array<int, Model> $models
     * @param array<mixed, array<int, Model>> $pivotDictionary
     */
    private function matchModels(array $models, string $relationName, array $pivotDictionary): void
    {
        foreach ($models as $model) {
            $parentId = $model->id ?? null;
            $related = $parentId !== null ? ($pivotDictionary[$parentId] ?? []) : [];
            $this->assignRelationToModel($model, $relationName, $related);
        }
    }
}
