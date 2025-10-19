<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced\AttributeProcessors;

use Larafony\Framework\Routing\Advanced\Attributes\Middleware;
use Larafony\Framework\Routing\Advanced\Attributes\RouteGroup;
use ReflectionAttribute;
use ReflectionClass;

readonly class ClassAttributesProcessor
{
    public ?RouteGroup $routeGroup;
    public ?Middleware $middleware;

    /**
     * @param ReflectionClass<object> $controller
     */
    public function __construct(ReflectionClass $controller)
    {
        /** @var array<int, ReflectionAttribute<RouteGroup>> $groupAttributes */
        $groupAttributes = $controller->getAttributes(RouteGroup::class);
        $this->routeGroup = $groupAttributes ? $groupAttributes[0]->newInstance() : null;
        /** @var array<int, ReflectionAttribute<Middleware>> $middlewareAttributes */
        $middlewareAttributes = $controller->getAttributes(Middleware::class);
        $this->middleware = $middlewareAttributes ? $middlewareAttributes[0]->newInstance() : null;
    }
}
