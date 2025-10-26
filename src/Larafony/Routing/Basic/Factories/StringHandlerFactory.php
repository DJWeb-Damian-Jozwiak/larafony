<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Basic\Factories;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Routing\Basic\Handlers\FunctionRouteHandler;
use Larafony\Framework\Routing\Basic\Handlers\InvocableClassRouteHandler;
use Larafony\Framework\Validation\Handlers\FormRequestAwareHandler;
use Psr\Http\Server\RequestHandlerInterface;

final class StringHandlerFactory
{
    public function __construct(
        private readonly ContainerContract $container,
    ) {
    }

    public function create(string $handler): RequestHandlerInterface
    {
        // Check if it's a class@method notation
        if (str_contains($handler, '@')) {
            return $this->createFromClassMethodNotation($handler);
        }

        // Check if it's an invocable class
        if (class_exists($handler)) {
            return $this->createInvocableClassHandler($handler);
        }

        // Otherwise, it's a function name
        return $this->createFunctionHandler($handler);
    }

    private function createFromClassMethodNotation(string $handler): FormRequestAwareHandler
    {
        [$class, $method] = explode('@', $handler, 2);
        // Use FormRequestAwareHandler to support both ServerRequest and FormRequest DTOs
        return new FormRequestAwareHandler($class, $method, $this->container);
    }

    private function createInvocableClassHandler(string $class): InvocableClassRouteHandler
    {
        return new InvocableClassRouteHandler($class, $this->container);
    }

    private function createFunctionHandler(string $function): FunctionRouteHandler
    {
        return new FunctionRouteHandler($function);
    }
}
