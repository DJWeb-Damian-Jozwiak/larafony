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
        array_map($this->registerListeners(...), $this->listenerClasses);
    }

    /**
     * @param class-string|object $classOrInstance
     */
    private function registerListeners(object|string $classOrInstance): void
    {
        $reflection = new ReflectionClass($classOrInstance);
        $callableTarget = is_object($classOrInstance) ? $classOrInstance : $reflection->getName();

        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        array_walk(
            $methods,
            fn (ReflectionMethod $method) => $this->registerMethodListeners($reflection, $method, $callableTarget)
        );
    }

    /**
     * @param ReflectionClass<object> $reflection
     * @param class-string|object $callableTarget
     */
    private function registerMethodListeners(
        ReflectionClass $reflection,
        ReflectionMethod $method,
        object|string $callableTarget,
    ): void {
        $attributes = $method->getAttributes(Listen::class);

        foreach ($attributes as $attribute) {
            /** @var Listen $listen */
            $listen = $attribute->newInstance();
            $eventClass = $listen->event ?? $this->inferEventClass($method);

            if ($eventClass === null) {
                $msg = 'Cannot infer event class for %s::%s(). Specify it in #[Listen] attribute.';
                throw new \RuntimeException(sprintf($msg, $reflection->getName(), $method->getName()));
            }

            $this->provider->listen($eventClass, [$callableTarget, $method->getName()], $listen->priority);
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
        return $type instanceof \ReflectionNamedType && ! $type->isBuiltin() ? $type->getName() : null;
    }
}
