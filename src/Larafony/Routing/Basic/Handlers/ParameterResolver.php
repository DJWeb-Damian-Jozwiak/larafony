<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Basic\Handlers;

use Larafony\Framework\Routing\Contracts\PreResolutionHookContract;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionFunctionAbstract;
use ReflectionNamedType;

/**
 * Simple parameter resolver that gets values from request attributes
 * (already populated by Route) and injects ServerRequestInterface
 */
final class ParameterResolver
{
    /**
     * Resolve callable arguments from request attributes
     *
     * @param ReflectionFunctionAbstract $reflection Reflection of the callable
     * @param ServerRequestInterface $request The request with route parameters in attributes
     * @param RequestHandlerInterface|null $handler Optional handler to check for pre-resolution hooks
     * @param callable|null $callable Optional callable for hook (deprecated, will be removed)
     *
     * @return array<int, mixed> The resolved arguments
     */
    public function resolve(
        ReflectionFunctionAbstract $reflection,
        ServerRequestInterface $request,
        ?RequestHandlerInterface $handler = null,
        ?callable $callable = null
    ): array {
        // Call pre-resolution hook if handler implements it
        if ($handler instanceof PreResolutionHookContract) {
            $request = $handler->beforeResolution($request, $callable ?? static fn () => null);
        }

        $parameters = $reflection->getParameters();

        return array_map(
            fn ($param) => $this->resolveParameter($param, $request),
            $parameters
        );
    }

    /**
     * Resolve single parameter from request
     */
    private function resolveParameter(\ReflectionParameter $parameter, ServerRequestInterface $request): mixed
    {
        $type = $parameter->getType();
        $name = $parameter->getName();

        // 1. ServerRequestInterface injection
        if ($type instanceof ReflectionNamedType
            && ($type->getName() === ServerRequestInterface::class || is_a($request, $type->getName()))) {
            return $request;
        }

        // 2. Get from request attributes (Route already populated them)
        $attributes = $request->getAttributes();
        $error = new \RuntimeException("Cannot resolve parameter \${$name} for route handler");
        return $attributes[$name] ?? throw $error;
    }
}
