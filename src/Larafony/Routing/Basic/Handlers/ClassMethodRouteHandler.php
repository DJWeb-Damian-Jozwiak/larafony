<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Basic\Handlers;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ClassMethodRouteHandler implements RequestHandlerInterface
{
    private readonly ParameterResolver $parameterResolver;
    private string $class {
        get => $this->class;
        set {
            if (! class_exists($value)) {
                throw new \InvalidArgumentException(sprintf('Class %s does not exist', $value));
            }
            $this->class = $value;
        }
    }

    private string $method {
        get => $this->method;
        set {
            if (! method_exists($this->class, $value)) {
                throw new \InvalidArgumentException(
                    sprintf('Method %s does not exist in class %s', $value, $this->class)
                );
            }
            $this->method = $value;
        }
    }

    public function __construct(
        string $class,
        string $method,
        private readonly ContainerContract $container,
        ?ParameterResolver $parameterResolver = null,
    ) {
        $this->class = $class;
        $this->method = $method;
        $this->parameterResolver = $parameterResolver ?? new ParameterResolver();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $instance = $this->container->get($this->class);
        $reflection = new \ReflectionMethod($this->class, $this->method);
        $arguments = $this->parameterResolver->resolve($reflection, $request);
        return $instance->{$this->method}(...$arguments);
    }
}
