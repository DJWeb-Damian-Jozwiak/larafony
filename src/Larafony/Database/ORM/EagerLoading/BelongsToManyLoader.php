<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\EagerLoading;

use Larafony\Framework\Database\ORM\Contracts\RelationContract;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Relations\BelongsToMany;

class BelongsToManyLoader implements RelationLoaderContract
{
    public function load(array $models, string $relationName, RelationContract $relation, array $nested): void
    {
        /** @var BelongsToMany $relation */
        $reflection = new \ReflectionClass($relation);

        $relatedClass = $this->getPropertyValue($reflection, $relation, 'related');
        $pivotTable = $this->getPropertyValue($reflection, $relation, 'pivot_table');
        $foreignPivotKey = $this->getPropertyValue($reflection, $relation, 'foreign_pivot_key');
        $relatedPivotKey = $this->getPropertyValue($reflection, $relation, 'related_pivot_key');

        // Collect parent IDs
        $parentIds = array_filter(
            array_map(fn ($model) => $model->id ?? null, $models)
        );

        if (empty($parentIds)) {
            return;
        }

        // Load pivot data
        $pivotData = DB::table($pivotTable)
            ->whereIn($foreignPivotKey, array_unique($parentIds))
            ->get();

        // Collect related IDs
        $relatedIds = array_unique(array_column($pivotData, $relatedPivotKey));

        if (empty($relatedIds)) {
            // No related models found
            foreach ($models as $model) {
                $model->relations->withEagerRelation($relationName, []);
            }
            return;
        }

        // Load related models
        $query = (new $relatedClass())->query_builder->whereIn('id', $relatedIds);

        // Support nested eager loading
        if (!empty($nested)) {
            $query->with($nested);
        }

        $relatedModels = $query->get();

        // Index related models by ID
        $relatedDictionary = [];
        foreach ($relatedModels as $relatedModel) {
            $relatedDictionary[$relatedModel->id] = $relatedModel;
        }

        // Group pivot data by parent ID
        $pivotDictionary = [];
        foreach ($pivotData as $pivot) {
            $parentId = $pivot[$foreignPivotKey];
            $relatedId = $pivot[$relatedPivotKey];
            $pivotDictionary[$parentId] = $pivotDictionary[$parentId] ?? [];
            if (isset($relatedDictionary[$relatedId])) {
                $pivotDictionary[$parentId][] = $relatedDictionary[$relatedId];
            }
        }

        // Match related models to parent models
        foreach ($models as $model) {
            $parentId = $model->id ?? null;
            $related = $parentId !== null ? ($pivotDictionary[$parentId] ?? []) : [];
            $model->relations->withEagerRelation($relationName, $related);
        }
    }

    private function getPropertyValue(\ReflectionClass $reflection, object $object, string $propertyName): mixed
    {
        $property = $reflection->getProperty($propertyName);
        return $property->getValue($object);
    }
}
