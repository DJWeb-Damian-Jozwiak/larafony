<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\EagerLoading;

use Larafony\Framework\Database\ORM\Contracts\RelationContract;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Relations\HasMany;

class HasManyLoader implements RelationLoaderContract
{
    public function load(array $models, string $relationName, RelationContract $relation, array $nested): void
    {
        /** @var HasMany $relation */
        $reflection = new \ReflectionClass($relation);

        $foreignKey = $this->getPropertyValue($reflection, $relation, 'foreign_key');
        $localKey = $this->getPropertyValue($reflection, $relation, 'local_key');
        $relatedClass = $this->getPropertyValue($reflection, $relation, 'related');

        // Collect local key values
        $localKeyValues = array_filter(
            array_map(fn ($model) => $model->$localKey ?? null, $models)
        );

        if (empty($localKeyValues)) {
            return;
        }

        // Load related models
        $query = new $relatedClass()->query_builder->whereIn($foreignKey, array_unique($localKeyValues));

        // Support nested eager loading
        if (!empty($nested)) {
            $query->with($nested);
        }

        $relatedModels = $query->get();

        // Group by foreign key
        $dictionary = [];
        foreach ($relatedModels as $relatedModel) {
            $fkValue = $relatedModel->$foreignKey;
            $dictionary[$fkValue] = $dictionary[$fkValue] ?? [];
            $dictionary[$fkValue][] = $relatedModel;
        }

        // Match related models to parent models
        foreach ($models as $model) {
            $lkValue = $model->$localKey ?? null;
            $related = $lkValue !== null ? ($dictionary[$lkValue] ?? []) : [];
            $model->relations->withEagerRelation($relationName, $related);
        }
    }

    private function getPropertyValue(\ReflectionClass $reflection, object $object, string $propertyName): mixed
    {
        $property = $reflection->getProperty($propertyName);
        return $property->getValue($object);
    }
}
