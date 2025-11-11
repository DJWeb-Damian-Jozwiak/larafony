<?php

declare(strict_types=1);

namespace Larafony\Framework\Events;

use Larafony\Framework\Events\Attributes\Listen;
use ReflectionClass;
use ReflectionMethod;

final class ListenerDiscovery
{
    /**
     * @param array<class-string|object> $listenerClasses
     */
    public function __construct(
        private readonly ListenerProvider $provider,
        private readonly array $listenerClasses = [],
    ) {
    }

    public function discover(): void
    {
        foreach ($this->listenerClasses as $classOrInstance) {
            if (is_object($classOrInstance)) {
                $this->registerListenersFromInstance($classOrInstance);
            } else {
                $this->registerListenersFromClass($classOrInstance);
            }
        }
    }

    /**
     * Register listeners from an object instance
     */
    private function registerListenersFromInstance(object $instance): void
    {
        $reflection = new ReflectionClass($instance);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(Listen::class);

            foreach ($attributes as $attribute) {
                /** @var Listen $listen */
                $listen = $attribute->newInstance();

                $eventClass = $listen->event ?? $this->inferEventClass($method);

                if ($eventClass === null) {
                    throw new \RuntimeException(
                        sprintf(
                            'Cannot infer event class for %s::%s(). ' .
                            'Either specify it in #[Listen] attribute or add a typed parameter.',
                            $reflection->getName(),
                            $method->getName()
                        )
                    );
                }

                // Use actual instance
                $this->provider->listen(
                    $eventClass,
                    [$instance, $method->getName()],
                    $listen->priority
                );
            }
        }
    }

    /**
     * @param class-string $className
     */
    private function registerListenersFromClass(string $className): void
    {
        $reflection = new ReflectionClass($className);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(Listen::class);

            foreach ($attributes as $attribute) {
                /** @var Listen $listen */
                $listen = $attribute->newInstance();

                $eventClass = $listen->event ?? $this->inferEventClass($method);

                if ($eventClass === null) {
                    throw new \RuntimeException(
                        sprintf(
                            'Cannot infer event class for %s::%s(). ' .
                            'Either specify it in #[Listen] attribute or add a typed parameter.',
                            $className,
                            $method->getName()
                        )
                    );
                }

                $this->provider->listen(
                    $eventClass,
                    [$className, $method->getName()],
                    $listen->priority
                );
            }
        }
    }

    /**
     * @return class-string|null
     */
    private function inferEventClass(ReflectionMethod $method): ?string
    {
        $parameters = $method->getParameters();

        if (count($parameters) === 0) {
            return null;
        }

        $type = $parameters[0]->getType();

        if ($type === null || $type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType) {
            return null;
        }

        /** @var \ReflectionNamedType $type */
        $typeName = $type->getName();

        if ($type->isBuiltin()) {
            return null;
        }

        /** @var class-string $typeName */
        return $typeName;
    }
}
