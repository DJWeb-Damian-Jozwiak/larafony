<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Basic\Handlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class FunctionRouteHandler implements RequestHandlerInterface
{
    private readonly ParameterResolver $parameterResolver;

    private string $function {
        get => $this->function;
        set {
            if (! function_exists($value)) {
                throw new \InvalidArgumentException(sprintf('Function %s does not exist', $value));
            }
            $this->function = $value;
        }
    }

    public function __construct(string $function, ?ParameterResolver $parameterResolver = null)
    {
        $this->function = $function;
        $this->parameterResolver = $parameterResolver ?? new ParameterResolver();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $function = $this->function;
        $reflection = new \ReflectionFunction($function);
        $arguments = $this->parameterResolver->resolve($reflection, $request);
        /** @phpstan-ignore-next-line */
        return $function(...$arguments);
    }
}
