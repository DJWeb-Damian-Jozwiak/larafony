<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced;

use Larafony\Framework\Container\Contracts\ContainerContract;

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
    private function resolveModel(RouteBinding $binding, mixed $value): ?object
    {
        $modelClass = $binding->modelClass;

        $model = $this->container->get($modelClass);
        return $model->{$binding->findMethod}($value);
    }
}