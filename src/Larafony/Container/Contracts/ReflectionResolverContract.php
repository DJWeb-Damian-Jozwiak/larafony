<?php

declare(strict_types=1);

namespace Larafony\Framework\Container\Contracts;

use Larafony\Framework\Container\Exceptions\ContainerError;
use ReflectionParameter;

interface ReflectionResolverContract
{
    /**
     * Get the constructor parameters for a given class.
     *
     * @param class-string $className The fully qualified class name
     *
     * @return array<int, ReflectionParameter> An array of constructor parameters
     */
    public function getConstructorParameters(string $className): array;

    /**
     * Get the type of a parameter.
     *
     * @param ReflectionParameter $parameter The parameter to analyze
     *
     * @return string|null The type of the parameter or null if it can't be determined
     */
    public function getParameterType(ReflectionParameter $parameter): ?string;

    /**
     * Check if a parameter has a default value.
     *
     * @param ReflectionParameter $parameter The parameter to check
     *
     * @return bool True if the parameter has a default value, false otherwise
     */
    public function hasDefaultValue(ReflectionParameter $parameter): bool;

    /**
     * Get the default value of a parameter.
     *
     * @param ReflectionParameter $parameter The parameter to get the default value from
     *
     * @return mixed The default value of the parameter
     *
     * @throws ContainerError If the parameter has no default value
     */
    public function getDefaultValue(ReflectionParameter $parameter): mixed;

    /**
     * Check if a parameter allows null.
     *
     * @param ReflectionParameter $parameter The parameter to check
     *
     * @return bool True if the parameter allows null, false otherwise
     */
    public function allowsNull(ReflectionParameter $parameter): bool;

    /**
     * Get the default value for a built-in type.
     *
     * @param string $type The built-in type
     *
     * @return mixed The default value for the type
     */
    public function getDefaultValueForBuiltInType(string $type): mixed;
}
