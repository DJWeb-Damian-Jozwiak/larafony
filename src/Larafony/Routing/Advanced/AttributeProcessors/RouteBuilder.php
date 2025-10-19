<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced\AttributeProcessors;

use Larafony\Framework\Http\Enums\HttpMethod;
use Larafony\Framework\Routing\Advanced\Attributes\Middleware;
use Larafony\Framework\Routing\Advanced\Attributes\Route as RouteAttribute;
use Larafony\Framework\Routing\Advanced\Route;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use ReflectionClass;
use ReflectionMethod;

class RouteBuilder
{
    public function __construct(
        private RouteHandlerFactory $handlerFactory,
    ) {
    }

    /**
     * @param ReflectionClass<object> $controller
     * @param ReflectionMethod $method
     * @param string $httpMethod
     * @param RouteAttribute $route
     *
     * @return Route
     */
    public function build(
        ReflectionClass $controller,
        ReflectionMethod $method,
        string $httpMethod,
        RouteAttribute $route,
    ): Route {
        $classAttributesProcessor = new ClassAttributesProcessor($controller);
        $groupPrefix = $classAttributesProcessor->routeGroup->name ?? '';
        $path = $groupPrefix . $route->path;
        $handler = [$controller->getName(), $method->getName()];
        $route = new Route(
            $path,
            HttpMethod::from(strtoupper($httpMethod)),
            $handler,
            $this->handlerFactory,
            $method->getName(),
        );
        $classMiddleware = $classAttributesProcessor->middleware;
        $classMiddleware?->addToRoute($route);
        $this->processMethodMiddleware($method, $route);
        new ParamAttributeProcessor()->process($route, $method);
        return $route;
    }

    private function processMethodMiddleware(ReflectionMethod $method, Route $route): void
    {
        $methodMiddlewareAttrs = $method->getAttributes(Middleware::class);
        foreach ($methodMiddlewareAttrs as $middlewareAttr) {
            /** @var Middleware $middleware */
            $middleware = $middlewareAttr->newInstance();
            $middleware->addToRoute($route);
        }
    }
}
