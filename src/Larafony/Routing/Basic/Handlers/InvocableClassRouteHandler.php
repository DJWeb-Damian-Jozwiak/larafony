<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Basic\Handlers;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class InvocableClassRouteHandler implements RequestHandlerInterface
{
    private readonly ParameterResolver $parameterResolver;

    private string $class {
        get => $this->class;
        set {
            if (! class_exists($value)) {
                throw new \InvalidArgumentException(sprintf('Class %s does not exist', $value));
            }
            if (! method_exists($value, '__invoke')) {
                throw new \InvalidArgumentException(sprintf('Class %s does not have an invoke method', $value));
            }
            $this->class = $value;
        }
    }

    public function __construct(
        string $class,
        private readonly ContainerContract $container,
        ?ParameterResolver $parameterResolver = null,
    ) {
        $this->class = $class;
        $this->parameterResolver = $parameterResolver ?? new ParameterResolver();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $instance = $this->container->get($this->class);
        $reflection = new \ReflectionMethod($this->class, '__invoke');
        $arguments = $this->parameterResolver->resolve($reflection, $request);
        return $instance(...$arguments);
    }
}
