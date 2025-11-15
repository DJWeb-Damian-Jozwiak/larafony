<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\EagerLoading;

use Larafony\Framework\Database\ORM\Contracts\RelationContract;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Relations\HasManyThrough;

class HasManyThroughLoader implements RelationLoaderContract
{
    public function load(array $models, string $relationName, RelationContract $relation, array $nested): void
    {
        /** @var HasManyThrough $relation */
        $reflection = new \ReflectionClass($relation);

        $relatedClass = $this->getPropertyValue($reflection, $relation, 'related');
        $throughClass = $this->getPropertyValue($reflection, $relation, 'through');
        $firstKey = $this->getPropertyValue($reflection, $relation, 'first_key');
        $secondKey = $this->getPropertyValue($reflection, $relation, 'second_key');
        $localKey = $this->getPropertyValue($reflection, $relation, 'local_key');
        $secondLocalKey = $this->getPropertyValue($reflection, $relation, 'second_local_key');

        // Collect parent IDs
        $parentIds = array_filter(
            array_map(fn ($model) => $model->$localKey ?? null, $models)
        );

        if (empty($parentIds)) {
            return;
        }

        // Load through models
        $throughTable = $throughClass::getTable();
        $throughModels = DB::table($throughTable)
            ->whereIn($firstKey, array_unique($parentIds))
            ->get();

        if (empty($throughModels)) {
            foreach ($models as $model) {
                $model->relations->withEagerRelation($relationName, []);
            }
            return;
        }

        // Collect through IDs
        $throughIds = array_unique(array_column($throughModels, $secondLocalKey));

        // Load related models
        $query = (new $relatedClass())->query_builder->whereIn($secondKey, $throughIds);

        // Support nested eager loading
        if (!empty($nested)) {
            $query->with($nested);
        }

        $relatedModels = $query->get();

        // Index related models by second key
        $relatedDictionary = [];
        foreach ($relatedModels as $relatedModel) {
            $key = $relatedModel->$secondKey;
            $relatedDictionary[$key] = $relatedDictionary[$key] ?? [];
            $relatedDictionary[$key][] = $relatedModel;
        }

        // Group through models by first key
        $throughDictionary = [];
        foreach ($throughModels as $through) {
            $parentId = $through[$firstKey];
            $throughId = $through[$secondLocalKey];
            $throughDictionary[$parentId] = $throughDictionary[$parentId] ?? [];

            if (isset($relatedDictionary[$throughId])) {
                foreach ($relatedDictionary[$throughId] as $relatedModel) {
                    $throughDictionary[$parentId][] = $relatedModel;
                }
            }
        }

        // Match related models to parent models
        foreach ($models as $model) {
            $parentId = $model->$localKey ?? null;
            $related = $parentId !== null ? ($throughDictionary[$parentId] ?? []) : [];
            $model->relations->withEagerRelation($relationName, $related);
        }
    }

    private function getPropertyValue(\ReflectionClass $reflection, object $object, string $propertyName): mixed
    {
        $property = $reflection->getProperty($propertyName);
        return $property->getValue($object);
    }
}
