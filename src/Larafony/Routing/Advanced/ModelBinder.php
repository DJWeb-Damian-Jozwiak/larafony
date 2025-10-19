<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced;

use Larafony\Framework\Container\Contracts\ContainerContract;
use ReflectionMethod;
use ReflectionNamedType;

readonly class ModelBinder
{
    public function __construct(
        private ContainerContract $container,
    ) {
    }

    /**
     * @param Route $route
     *
     * @return array<string, mixed>
     */
    public function resolveBindings(Route $route): array
    {
        $boundParameters = array_map(static fn ($parameterValue) => $parameterValue, $route->parameters);
        $bindings = $route->bindings;
        $parameters = array_intersect_key($route->parameters, $route->bindings);
        foreach ($parameters as $name => $value) {
            $binding = $bindings[$name];
            $model = $this->resolveModel($binding, $value);
            $boundParameters[$name] = $model;
        }
        return $boundParameters;
    }

    /**
     * Resolve bindings based on method parameter type hints
     *
     * @param Route $route
     * @param string $controllerClass
     * @param string $methodName
     *
     * @return array<string, mixed>
     */
    public function resolveFromMethodSignature(Route $route, string $controllerClass, string $methodName): array
    {
        $boundParameters = $route->parameters;

        $reflection = new ReflectionMethod($controllerClass, $methodName);
        $parameters = $reflection->getParameters();
        // Skip if parameter is not in route parameters
        $parameters = array_filter(
            $parameters,
            static fn (\ReflectionParameter $param) => isset($route->parameters[$param->getName()])
        );
        // Skip if already bound
        $parameters = array_filter(
            $parameters,
            static fn (\ReflectionParameter $param) => ! isset($route->bindings[$param->getName()])
        );

        foreach ($parameters as $parameter) {
            // Check if type is a named type (class)
            if ($parameter->getType() instanceof ReflectionNamedType && ! $parameter->getType()->isBuiltin()) {
                $binding = new RouteBinding($parameter->getType()->getName());
                $model = $this->resolveModel($binding, $route->parameters[$parameter->getName()]);
                $boundParameters[$parameter->getName()] = $model;
            }
        }

        return $boundParameters;
    }

    private function resolveModel(RouteBinding $binding, mixed $value): ?object
    {
        $modelClass = $binding->modelClass;

        $model = $this->container->get($modelClass);
        return $model->{$binding->findMethod}($value);
    }
}
