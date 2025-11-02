<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Basic\Handlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ClosureRouteHandler implements RequestHandlerInterface
{
    private readonly ParameterResolver $parameterResolver;

    public function __construct(
        private readonly \Closure $handler,
        ?ParameterResolver $parameterResolver = null,
    ) {
        $this->parameterResolver = $parameterResolver ?? new ParameterResolver();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $reflection = new \ReflectionFunction($this->handler);
        $arguments = $this->parameterResolver->resolve($reflection, $request);
        return ($this->handler)(...$arguments);
    }
}
