<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced;

use Larafony\Framework\Routing\Advanced\AttributeProcessors\RouteBuilder;
use Larafony\Framework\Routing\Advanced\Attributes\Route as RouteAttribute;
use Larafony\Framework\Routing\Basic\RouteHandlerFactory;
use ReflectionClass;
use ReflectionMethod;

readonly class AttributeRouteLoader
{
    public function __construct(
        private AttributeRouteScanner $scanner,
        private RouteHandlerFactory $handlerFactory
    ) {
    }

    /**
     * @return array<int, Route>
     */
    public function loadFromDirectory(string $path): array
    {
        $classes = $this->scanner->scanDirectory($path);
        return array_map($this->loadFromController(...), $classes);
    }

    /**
     * @param ReflectionClass<object> $controller
     *
     * @return array<int, Route>
     */
    public function loadFromController(ReflectionClass $controller): array
    {
        $routes = [];
        // Process each method
        foreach ($controller->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $routeAttributes = $method->getAttributes(RouteAttribute::class);

            foreach ($routeAttributes as $routeAttribute) {
                /** @var RouteAttribute $routeAttr */
                $routeAttr = $routeAttribute->newInstance();

                // Create route for each HTTP method
                foreach ($routeAttr->methods as $httpMethod) {
                    $routes[] = new RouteBuilder($this->handlerFactory)
                        ->build($controller, $method, $httpMethod, $routeAttr);
                }
            }
        }

        return $routes;
    }
}
