<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced\AttributeProcessors;

use Larafony\Framework\Routing\Advanced\Attributes\RouteParam;
use Larafony\Framework\Routing\Advanced\Route;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

class ParamAttributeProcessor
{
    public function process(Route $route, ReflectionMethod $method): void
    {
        $this->processAttributes($route, $method);
        $this->processMethodParams($route, $method);
    }

    private function processAttributes(Route $route, ReflectionMethod $method): void
    {
        $paramAttributes = $method->getAttributes(RouteParam::class);
        $params = array_map(static fn (\ReflectionAttribute $param) => $param->newInstance(), $paramAttributes);
        foreach ($params as $param) {
            $route->bind($param->name, $param->bind);
        }
    }

    private function processMethodParams(Route $route, ReflectionMethod $method): void
    {
        $params = $method->getParameters();
        $params = array_filter($params, $this->allowedReflectionParam(...));
        foreach ($params as $param) {
            /** @var ReflectionNamedType $type */
            $type = $param->getType();
            $route->bind($param->getName(), $type->getName());
        }
    }

    private function allowedReflectionParam(ReflectionParameter $param): bool
    {
        return $param->getType() instanceof ReflectionNamedType && ! $param->getType()->isBuiltin();
    }
}
