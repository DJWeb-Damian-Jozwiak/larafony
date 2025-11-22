<?php

declare(strict_types=1);

namespace Larafony\Framework\Scheduler;

use Larafony\Framework\Scheduler\Attributes\Serialize;
use Larafony\Framework\Scheduler\Contracts\JobContract;
use ReflectionProperty;

abstract class Job implements JobContract
{
    /**
     * @var array<string, mixed>
     */
    private array $serializableProperties = [];

    public function __construct()
    {
        $this->collectSerializableProperties();
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return array_map(fn (string $propertyName) => $this->{$propertyName}, $this->serializableProperties);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->collectSerializableProperties();
        foreach ($this->serializableProperties as $serializeName => $propertyName) {
            if (isset($data[$serializeName])) {
                $this->{$propertyName} = $data[$serializeName];
            }
        }

        $this->collectSerializableProperties();
    }

    protected function collectSerializableProperties(): void
    {
        $reflector = new \ReflectionClass($this);
        $properties = $reflector->getProperties();
        $properties = array_filter($properties, static fn (\ReflectionProperty $property) => $property->isPublic());
        $properties = array_filter($properties, $this->filterProperty(...));
        $this->parseSerializable($properties);

        $constructor = $reflector->getConstructor();
        if ($constructor) {
            $parameters = $constructor->getParameters();
            $parameters = array_filter($parameters, $this->filterProperty(...));
            $this->parseSerializable($parameters);
        }
    }

    protected function filterProperty(\ReflectionParameter|ReflectionProperty $property): bool
    {
        $attribute = $property->getAttributes(Serialize::class)[0] ?? null;
        return $attribute !== null;
    }

    private function parseSerializable(array $properties): void
    {
        array_walk($properties, function (\ReflectionParameter|ReflectionProperty $property): void {
            $attribute = $property->getAttributes(Serialize::class)[0] ?? null;
            $name = $attribute->newInstance()->name ?? $property->getName();
            $this->serializableProperties[$name] = $property->getName();
        });
    }
}
