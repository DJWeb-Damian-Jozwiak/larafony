<?php

declare(strict_types=1);

namespace Larafony\Framework\Container\Resolvers;

use Larafony\Framework\Container\Contracts\AutowireContract;
use Larafony\Framework\Container\Contracts\ReflectionResolverContract;
use Larafony\Framework\Container\Exceptions\NotFoundError;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

/**
 * Class Autowire
 *
 * Responsible for automatically resolving and injecting dependencies.
 */
class Autowire implements AutowireContract
{
    public function __construct(
        private ContainerInterface $container,
        private ?ReflectionResolverContract $resolver = null,
    ) {
        $this->resolver ??= new ReflectionResolver();
    }

    /**
     * Autowire and instantiate a class.
     *
     * @template T of object
     *
     * @param class-string<T> $className The name of the class to instantiate
     *
     * @return T The instantiated object
     *
     * @throws ReflectionException If the class cannot be reflected
     * @throws NotFoundError If a dependency cannot be resolved
     */
    public function instantiate(string $className): object
    {
        $reflectionClass = new ReflectionClass($className);
        $constructor = $reflectionClass->getConstructor();

        if ($constructor === null) {
            return $reflectionClass->newInstance();
        }

        $parameters = $this->resolver->getConstructorParameters($className);
        $arguments = $this->resolveParameters($parameters);

        return $reflectionClass->newInstanceArgs($arguments);
    }

    /**
     * Resolve parameters for dependency injection.
     *
     * @param array<int, ReflectionParameter> $parameters
     *
     * @return array<mixed>
     */
    private function resolveParameters(array $parameters): array
    {
        return array_map(function (ReflectionParameter $parameter) {
            $parameterName = $parameter->getName();
            /** @var string $type */
            $type = $this->resolver->getParameterType($parameter);

            return match (true) {
                // 1. return given value if exists
                $this->container->has($parameterName) => $this->container->get($parameterName),
                $this->container->has($type) => $this->container->get($type),
                // 2. return default value if exist
                $this->resolver->hasDefaultValue($parameter) => $this->resolver->getDefaultValue($parameter),

                // 3. return null if allowed
                $this->resolver->allowsNull($parameter) => null,

                // 4. for builtin types return default value
                $type && $this->isBuiltInType($type) => $this->resolver->getDefaultValueForBuiltInType($type),

                // 5. for object check recursively
                $type && class_exists($type) => $this->instantiate($type),
                // otherwise throw not found exception
                default => throw new NotFoundError(
                    "Unable to resolve parameter {$parameterName} of type {$type}"
                )
            };
        }, $parameters);
    }

    private function isBuiltInType(string $type): bool
    {
        return in_array($type, ['int', 'float', 'string', 'bool', 'array']);
    }
}