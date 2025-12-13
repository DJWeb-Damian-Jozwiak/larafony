<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\EagerLoading;

use Larafony\Framework\Database\ORM\Contracts\RelationContract;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\QueryBuilders\ModelQueryBuilder;
use Larafony\Framework\Database\ORM\Relations\HasManyThrough;

class HasManyThroughLoader extends BaseRelationLoader
{
    public function load(
        array $models,
        string $relationName,
        RelationContract $relation,
        array $nested
    ): void {
        assert($relation instanceof HasManyThrough);
        $this->initReflection($relation);

        $config = $this->extractRelationConfig($relation);
        $parentIds = $this->collectKeyValues($models, $config->localKey);

        if ($parentIds === []) {
            return;
        }

        $throughModels = $this->loadThroughModels($config, $parentIds);

        if ($throughModels === []) {
            $this->assignEmptyRelations($models, $relationName);

            return;
        }

        $throughIds = array_unique(array_column($throughModels, $config->secondLocalKey));
        $relatedModels = $this->loadRelatedModels($config, $throughIds, $nested);
        $relatedDictionary = $this->groupModelsBy($relatedModels, $config->secondKey);
        $throughDictionary = $this->buildThroughDictionary($throughModels, $config, $relatedDictionary);

        $this->matchModels($models, $relationName, $throughDictionary, $config->localKey);
    }

    private function extractRelationConfig(HasManyThrough $relation): HasManyThroughConfigDto
    {
        /** @var class-string<Model> $relatedClass */
        $relatedClass = $this->getPropertyValue($relation, 'related');
        /** @var class-string<Model> $throughClass */
        $throughClass = $this->getPropertyValue($relation, 'through');

        return new HasManyThroughConfigDto(
            relatedClass: $relatedClass,
            throughClass: $throughClass,
            firstKey: $this->getPropertyValue($relation, 'first_key'),
            secondKey: $this->getPropertyValue($relation, 'second_key'),
            localKey: $this->getPropertyValue($relation, 'local_key'),
            secondLocalKey: $this->getPropertyValue($relation, 'second_local_key'),
        );
    }

    /**
     * @param array<mixed> $parentIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadThroughModels(HasManyThroughConfigDto $config, array $parentIds): array
    {
        $throughTable = $config->throughClass::getTable();

        return DB::table($throughTable)
            ->whereIn($config->firstKey, array_unique($parentIds))
            ->get();
    }

    /**
     * @param array<mixed> $throughIds
     * @param array<string> $nested
     *
     * @return array<int, Model>
     */
    private function loadRelatedModels(HasManyThroughConfigDto $config, array $throughIds, array $nested): array
    {
        /** @var ModelQueryBuilder $query */
        $query = new $config->relatedClass()->query_builder->whereIn($config->secondKey, $throughIds);

        return $this->buildQueryWithNested($query, $nested)->get();
    }

    /**
     * @param array<int, array<string, mixed>> $throughModels
     * @param array<mixed, array<int, Model>> $relatedDictionary
     *
     * @return array<mixed, array<int, Model>>
     */
    private function buildThroughDictionary(
        array $throughModels,
        HasManyThroughConfigDto $config,
        array $relatedDictionary
    ): array {
        $dictionary = [];
        foreach ($throughModels as $through) {
            $parentId = $through[$config->firstKey];
            $throughId = $through[$config->secondLocalKey];
            $dictionary[$parentId] ??= [];

            $this->appendRelatedModels($dictionary, $parentId, $relatedDictionary, $throughId);
        }

        return $dictionary;
    }

    /**
     * @param array<mixed, array<int, Model>> $dictionary
     * @param array<mixed, array<int, Model>> $relatedDictionary
     */
    private function appendRelatedModels(
        array &$dictionary,
        mixed $parentId,
        array $relatedDictionary,
        mixed $throughId
    ): void {
        if (! isset($relatedDictionary[$throughId])) {
            return;
        }

        foreach ($relatedDictionary[$throughId] as $relatedModel) {
            $dictionary[$parentId][] = $relatedModel;
        }
    }

    /**
     * @param array<int, Model> $models
     * @param array<mixed, array<int, Model>> $throughDictionary
     */
    private function matchModels(
        array $models,
        string $relationName,
        array $throughDictionary,
        string $localKey
    ): void {
        foreach ($models as $model) {
            $parentId = $model->$localKey ?? null;
            $related = $parentId !== null ? ($throughDictionary[$parentId] ?? []) : [];
            $this->assignRelationToModel($model, $relationName, $related);
        }
    }
}
