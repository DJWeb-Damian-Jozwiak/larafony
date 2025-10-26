<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM;

use Larafony\Framework\Database\ORM\Contracts\PropertyChangesContract;
use Larafony\Framework\Database\ORM\Decorators\EntityManager;
use Larafony\Framework\Database\ORM\QueryBuilders\ModelQueryBuilder;
use Larafony\Framework\Database\ORM\Relations\RelationDecorator;
use Larafony\Framework\Database\ORM\Relations\RelationFactory;
use LogicException;

abstract class Model implements PropertyChangesContract, \JsonSerializable
{
    abstract public string $table { get; }

    public protected(set) string $primary_key_name = 'id';
    public protected(set) ModelQueryBuilder $query_builder;
    public private(set) PropertyObserver $observer;

    public int|string $id {
        get => $this->id;
        set {
            $this->id = $value;
            $this->markPropertyAsChanged('id');
        }
    }

    public bool $is_new {
        get => $this->observer->is_new;
    }

    protected private(set) RelationDecorator $relations;

    /**
     * @var array<string, string>
     */
    protected array $casts = [];
    private RelationFactory $relation_factory;

    private EntityManager $entity_manager;

    final public function __construct()
    {
        $this->query_builder = new ModelQueryBuilder($this);
        $this->observer = new PropertyObserver($this);
        $this->entity_manager = new EntityManager($this);
        $this->relation_factory = new RelationFactory();
        $this->relations = new RelationDecorator($this);
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
    public function jsonSerialize(): array
    {
        $msg = 'Direct serialization of models is not allowed. Use a Data Transfer Object (DTO) for serialization';
        throw new LogicException($msg);
    }

    public function findForRoute(string|int $value): static
    {
        return self::query()->where('id', '=', $value)->first();
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
            if (isset($this->casts[$key])) {
                $value = $this->castAttribute($value, $this->casts[$key]);
            }
            $this->$key = $value;
        }
        return $this;
    }

    public function markPropertyAsChanged(string $property_name): void
    {
        $this->observer->markPropertyAsChanged(
            $property_name,
            $this->$property_name
        );
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

    protected function castAttribute(mixed $value, string $type): mixed
    {
        return match (true) {
            $type === 'datetime' => $value instanceof \DateTimeImmutable
                ? $value
                : new \DateTimeImmutable($value),
            is_subclass_of($type, \BackedEnum::class) => $type::from($value),
            is_subclass_of($type, Contracts\Castable::class) => $type::from($value),
            default => $value,
        };
    }
}
