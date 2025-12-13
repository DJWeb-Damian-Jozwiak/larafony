<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\EagerLoading;

use Larafony\Framework\Database\ORM\Contracts\RelationContract;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\QueryBuilders\ModelQueryBuilder;
use ReflectionClass;

abstract class BaseRelationLoader implements RelationLoaderContract
{
    /** @var ReflectionClass<RelationContract> */
    protected ReflectionClass $reflection;

    /**
     * @param array<int, Model> $models
     * @param array<string> $nested
     */
    abstract public function load(array $models, string $relationName, RelationContract $relation, array $nested): void;

    protected function initReflection(RelationContract $relation): void
    {
        /** @var ReflectionClass<RelationContract> $reflection */
        $reflection = new ReflectionClass($relation);
        $this->reflection = $reflection;
    }

    protected function getPropertyValue(object $object, string $propertyName): mixed
    {
        $property = $this->reflection->getProperty($propertyName);

        return $property->getValue($object);
    }

    /**
     * @param array<int, Model> $models
     *
     * @return array<mixed>
     */
    protected function collectKeyValues(array $models, string $keyName): array
    {
        return array_filter(
            array_map(static fn ($model) => $model->$keyName ?? null, $models)
        );
    }

    /**
     * @param array<mixed>|Model|null $related
     */
    protected function assignRelationToModel(Model $model, string $relationName, array|Model|null $related): void
    {
        $model->relations->withEagerRelation($relationName, $related);
    }

    /**
     * @param array<int, Model> $models
     */
    protected function assignEmptyRelations(array $models, string $relationName, bool $asArray = true): void
    {
        array_walk(
            $models,
            fn (Model $model) => $this->assignRelationToModel($model, $relationName, $asArray ? [] : null)
        );
    }

    /**
     * @param array<string> $nested
     */
    protected function buildQueryWithNested(ModelQueryBuilder $query, array $nested): ModelQueryBuilder
    {
        return $query->with($nested);
    }

    /**
     * @param array<int, Model> $relatedModels
     *
     * @return array<mixed, Model>
     */
    protected function indexModelsBy(array $relatedModels, string $keyName): array
    {
        $dictionary = [];
        foreach ($relatedModels as $model) {
            $dictionary[$model->$keyName] = $model;
        }

        return $dictionary;
    }

    /**
     * @param array<int, Model> $relatedModels
     *
     * @return array<mixed, array<int, Model>>
     */
    protected function groupModelsBy(array $relatedModels, string $keyName): array
    {
        $dictionary = [];
        foreach ($relatedModels as $model) {
            $keyValue = $model->$keyName;
            $dictionary[$keyValue] ??= [];
            $dictionary[$keyValue][] = $model;
        }

        return $dictionary;
    }
}
