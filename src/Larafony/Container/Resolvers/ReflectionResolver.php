<?php

declare(strict_types=1);

namespace Larafony\Framework\Container\Resolvers;

use Larafony\Framework\Container\Contracts\ReflectionResolverContract;
use Larafony\Framework\Container\Exceptions\ContainerError;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;

class ReflectionResolver implements ReflectionResolverContract
{
    /**
     * Get the constructor parameters for a given class.
     *
     * @param class-string $className The fully qualified class name
     *
     * @return array<int, ReflectionParameter> An array of constructor parameters
     *
     * @throws ReflectionException If the class does not exist or has no constructor
     */
    public function getConstructorParameters(string $className): array
    {
        $reflectionClass = new ReflectionClass($className);
        $constructor = $reflectionClass->getConstructor();

        return $constructor?->getParameters() ?? [];
    }

    /**
     * Get the type of a parameter.
     *
     * @param ReflectionParameter $parameter The parameter to analyze
     *
     * @return string|null The type of the parameter or null if it can't be determined
     */
    public function getParameterType(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();
        return $type instanceof ReflectionNamedType ? $type->getName() : null;
    }

    /**
     * Check if a parameter has a default value.
     *
     * @param ReflectionParameter $parameter The parameter to check
     *
     * @return bool True if the parameter has a default value, false otherwise
     */
    public function hasDefaultValue(ReflectionParameter $parameter): bool
    {
        return $parameter->isDefaultValueAvailable();
    }

    /**
     * Get the default value of a parameter.
     *
     * @param ReflectionParameter $parameter The parameter to get the default value from
     *
     * @return mixed The default value of the parameter
     *
     * @throws ContainerError|ReflectionException If the parameter has no default value
     */
    public function getDefaultValue(ReflectionParameter $parameter): mixed
    {
        if (! $this->hasDefaultValue($parameter)) {
            throw new ContainerError("Parameter {$parameter->getName()} has no default value.");
        }

        return $parameter->getDefaultValue();
    }

    /**
     * Check if a parameter allows null.
     *
     * @param ReflectionParameter $parameter The parameter to check
     *
     * @return bool True if the parameter allows null, false otherwise
     */
    public function allowsNull(ReflectionParameter $parameter): bool
    {
        return $parameter->allowsNull();
    }

    /**
     * Get the default value for a built-in type.
     *
     * @param string $type The built-in type
     *
     * @return mixed The default value for the type
     */
    public function getDefaultValueForBuiltInType(string $type): mixed
    {
        return match ($type) {
            'int' => 0,
            'float' => 0.0,
            'string' => '',
            'bool' => false,
            'array' => [],
            default => null,
        };
    }
}
