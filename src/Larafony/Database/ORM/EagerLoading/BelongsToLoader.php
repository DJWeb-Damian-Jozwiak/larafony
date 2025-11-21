<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\EagerLoading;

use Larafony\Framework\Database\ORM\Contracts\RelationContract;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Relations\BelongsTo;

class BelongsToLoader implements RelationLoaderContract
{
    public function load(array $models, string $relationName, RelationContract $relation, array $nested): void
    {
        /** @var BelongsTo $relation */
        $reflection = new \ReflectionClass($relation);

        $foreignKey = $this->getPropertyValue($reflection, $relation, 'foreign_key');
        $localKey = $this->getPropertyValue($reflection, $relation, 'local_key');
        $relatedClass = $this->getPropertyValue($reflection, $relation, 'related');

        // Collect foreign key values
        $foreignKeyValues = array_filter(
            array_map(fn ($model) => $model->$foreignKey ?? null, $models)
        );

        if (empty($foreignKeyValues)) {
            return;
        }

        // Load related models
        $query = new $relatedClass()->query_builder->whereIn($localKey, array_unique($foreignKeyValues));

        // Support nested eager loading
        if (!empty($nested)) {
            $query->with($nested);
        }

        $relatedModels = $query->get();

        // Index by local key
        $dictionary = [];
        foreach ($relatedModels as $relatedModel) {
            $dictionary[$relatedModel->$localKey] = $relatedModel;
        }

        // Match related models to parent models
        foreach ($models as $model) {
            $fkValue = $model->$foreignKey ?? null;
            $relatedModel = $fkValue !== null ? ($dictionary[$fkValue] ?? null) : null;
            $model->relations->withEagerRelation($relationName, $relatedModel);
        }
    }

    private function getPropertyValue(\ReflectionClass $reflection, object $object, string $propertyName): mixed
    {
        $property = $reflection->getProperty($propertyName);
        return $property->getValue($object);
    }
}
