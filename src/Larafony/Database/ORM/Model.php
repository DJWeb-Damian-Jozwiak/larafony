<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM;

use Larafony\Framework\Core\Support\Str;
use Larafony\Framework\Database\ORM\Casters\AttributeCaster;
use Larafony\Framework\Database\ORM\Contracts\AttributeCasterContract;
use Larafony\Framework\Database\ORM\Contracts\PropertyChangesContract;
use Larafony\Framework\Database\ORM\Decorators\EntityManager;
use Larafony\Framework\Database\ORM\QueryBuilders\ModelQueryBuilder;
use Larafony\Framework\Database\ORM\Relations\RelationDecorator;
use Larafony\Framework\Database\ORM\Relations\RelationFactory;
use LogicException;

abstract class Model implements PropertyChangesContract, \JsonSerializable
{
    public string $table {
        get => $this->_table ?? ($this::class |> Str::classBasename(...) |> Str::snake(...) |> Str::pluralize(...));
    }

    public protected(set) string $primary_key_name = 'id';
    public protected(set) ModelQueryBuilder $query_builder;
    public private(set) PropertyObserver $observer;

    public protected(set) bool $use_uuid = false;

    public int|string|null $id {
        // isset outside a class with property hooks is a known bug in PHP
        // it's not going to be fixed anytime soon
        // see https://github.com/php/php-src/issues/20703
        // see https://github.com/php/php-src/issues/18318
        get => $this->id ?? null;
        set {
            $this->id = $value;
            $this->markPropertyAsChanged('id');
        }
    }

    public bool $is_new {
        get => $this->observer->is_new;
    }

    public private(set) RelationDecorator $relations;
    protected ?string $_table = null;

    private RelationFactory $relation_factory;

    private EntityManager $entity_manager;

    private AttributeCasterContract $attribute_caster;

    final public function __construct()
    {
        $this->query_builder = new ModelQueryBuilder($this);
        $this->observer = new PropertyObserver($this);
        $this->entity_manager = new EntityManager($this);
        $this->relation_factory = new RelationFactory();
        $this->relations = new RelationDecorator($this);
        $this->attribute_caster = new AttributeCaster();
    }

    /**
     * Re-initialize components when model is cloned (for hydration)
     */
    public function __clone(): void
    {
        $this->query_builder = new ModelQueryBuilder($this);
        $this->observer = new PropertyObserver($this);
        $this->entity_manager = new EntityManager($this);
        $this->relation_factory = new RelationFactory();
        $this->relations = new RelationDecorator($this);
        $this->attribute_caster = new AttributeCaster();
    }

    /**
     * Models cannot be directly serialized to JSON.
     *
     * This design decision enforces separation of concerns:
     * - Models represent database entities with behavior and state management
     * - DTOs (Data Transfer Objects) define API contracts and serialization format
     *
     * Direct model serialization would couple internal database structure to API responses,
     * making it harder to evolve the schema independently from API contracts.
     * Always use DTOs for serialization to maintain flexibility and proper layering.
     *
     * @return array<string, mixed>
     *
     * @throws LogicException Always thrown to prevent direct serialization
     */
    final public function jsonSerialize(): array
    {
        $msg = 'Direct serialization of models is not allowed. Use a Data Transfer Object (DTO) for serialization';
        throw new LogicException($msg);
    }

    public function findForRoute(string|int $value): static
    {
        $result = self::query()->where('id', '=', $value)->first();

        if ($result === null) {
            throw new \Larafony\Framework\Core\Exceptions\NotFoundError(
                sprintf('Model %s with id %s not found', static::class, $value),
            );
        }

        return $result;
    }

    public function delete(): int
    {
        return self::query()->where($this->primary_key_name, '=', $this->id)->delete();
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return $this
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if (! property_exists($this, $key)) {
                continue;
            }
            $value = $this->attribute_caster->cast($value, $key, $this);
            $this->$key = $value;
        }
        return $this;
    }

    public function markPropertyAsChanged(string $property_name): void
    {
        $this->observer->markPropertyAsChanged(
            $property_name,
            $this->$property_name,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->observer->changedProperties;
    }

    public function save(): void
    {
        $this->entity_manager->save();
    }

    public static function query(): ModelQueryBuilder
    {
        return new static()->query_builder;
    }

    public static function getTable(): string
    {
        return new static()->table;
    }
}
