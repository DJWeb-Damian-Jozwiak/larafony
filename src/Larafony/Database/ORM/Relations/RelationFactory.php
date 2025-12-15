<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Relations;

use Larafony\Framework\Database\ORM\Attributes\BelongsTo as BelongsToAttribute;
use Larafony\Framework\Database\ORM\Attributes\BelongsToMany as BelongsToManyAttribute;
use Larafony\Framework\Database\ORM\Attributes\HasMany as HasManyAttribute;
use Larafony\Framework\Database\ORM\Attributes\HasManyThrough as HasManyThroughAttribute;
use Larafony\Framework\Database\ORM\Contracts\RelationContract;
use Larafony\Framework\Database\ORM\Enums\RelationType;
use Larafony\Framework\Database\ORM\Model;

class RelationFactory
{
    public static function belongsTo(
        Model $parent,
        BelongsToAttribute $attribute,
    ): RelationContract {
        $relation = new self()->create(
            RelationType::belongsTo,
            $parent,
            $attribute->related,
            $attribute->foreign_key,
            $attribute->local_key
        );
        $relation->addConstraints();
        return $relation;
    }

    public static function belongsToMany(
        Model $parent,
        BelongsToManyAttribute $attribute,
    ): RelationContract {
        $relation = new BelongsToMany(
            $parent,
            $attribute->related,
            $attribute->pivot_table,
            $attribute->foreign_pivot_key,
            $attribute->related_pivot_key
        );
        $relation->addConstraints();
        return $relation;
    }

    public static function hasManyThrough(
        Model $parent,
        HasManyThroughAttribute $attribute,
    ): RelationContract {
        $relation = new HasManyThrough(
            $parent,
            $attribute->related,
            $attribute->through,
            $attribute->first_key,
            $attribute->second_key,
            $attribute->local_key,
            $attribute->second_local_key
        );
        $relation->addConstraints();
        return $relation;
    }

    public static function hasMany(
        Model $parent,
        HasManyAttribute $attribute,
    ): RelationContract {
        $relation = new self()->create(
            RelationType::hasMany,
            $parent,
            $attribute->related,
            $attribute->foreign_key,
            $attribute->local_key
        );
        $relation->addConstraints();
        return $relation;
    }

    public static function hasOne(
        Model $parent,
        HasManyAttribute $attribute,
    ): RelationContract {
        $relation = new self()->create(
            RelationType::hasOne,
            $parent,
            $attribute->related,
            $attribute->foreign_key,
            $attribute->local_key
        );
        $relation->addConstraints();
        return $relation;
    }

    /**
     * Create basic relation types (belongsTo, hasMany).
     *
     * Note: belongsToMany and hasManyThrough are created directly in their
     * respective factory methods as they require different constructor parameters.
     *
     * @param RelationType $type
     * @param Model $parent
     * @param class-string<Model> $related
     * @param string $foreignKey
     * @param string $localKey
     *
     * @return RelationContract
     */
    private function create(
        RelationType $type,
        Model $parent,
        string $related,
        string $foreignKey,
        string $localKey
    ): RelationContract {
        return match ($type->value) {
            'hasOne' => new HasOne($parent, $related, $foreignKey, $localKey),
            'hasMany' => new HasMany($parent, $related, $foreignKey, $localKey),
            'belongsTo' => new BelongsTo(
                $parent,
                $related,
                $foreignKey,
                $localKey
            ),
            // These relation types are created directly in their factory methods
            // due to different constructor signatures
            'belongsToMany', 'hasManyThrough' => throw new \LogicException(
                "Relation type {$type->value} cannot be created via create() method"
            ),
        };
    }
}
