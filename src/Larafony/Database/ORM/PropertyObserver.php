<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM;

use Larafony\Framework\Clock\Contracts\Clock;
use Larafony\Framework\Database\ORM\Contracts\NotifyPropertyChangesContract;
use Stringable;

class PropertyObserver implements NotifyPropertyChangesContract
{
    public bool $is_new {
        get => ! isset($this->changedProperties[$this->model->primary_key_name]) || ! isset($this->model->id);
    }

    /**
     * @var array<string, int|string|float|null>
     */
    public private(set) array $changedProperties = [];

    public function __construct(private Model $model)
    {
    }

    public function markPropertyAsChanged(
        string $propertyName,
        mixed $value,
    ): void {
        $this->changedProperties[$propertyName] = $this->toString($value);
    }

    public function toString(mixed $value): mixed
    {
        return match (true) {
            $value instanceof Stringable => (string) $value,
            $value instanceof \DateTimeInterface || $value instanceof Clock => $value->format('Y-m-d H:i:s'),
            is_array($value), is_object($value) => json_encode($value),
            default => $value,
        };
    }

    /**
     * @return array<string, int|string|float|null>
     */
    public function getChangedProperties(): array
    {
        return $this->changedProperties;
    }
}
