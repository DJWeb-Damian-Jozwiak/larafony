<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Handlers;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Routing\Basic\Handlers\ParameterResolver;
use Larafony\Framework\Routing\Contracts\PreResolutionHookContract;
use Larafony\Framework\Validation\Helpers\FormRequestFactory;
use Larafony\Framework\Validation\Helpers\FormRequestTypeDetector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionMethod;

/**
 * Route handler with automatic FormRequest validation.
 *
 * Extends basic routing with FormRequest support without modifying existing code.
 * Acts as a facade for validation helpers.
 *
 * Complexity: 5 (facade pattern)
 */
final class FormRequestAwareHandler implements RequestHandlerInterface, PreResolutionHookContract
{
    private string $class;
    private string $method;
    private FormRequestTypeDetector $typeDetector;
    private FormRequestFactory $factory;
    private ParameterResolver $parameterResolver;

    public function __construct(
        string $class,
        string $method,
        private readonly ContainerContract $container,
        ?FormRequestTypeDetector $typeDetector = null,
        ?FormRequestFactory $factory = null,
        ?ParameterResolver $parameterResolver = null,
    ) {
        $this->typeDetector = $typeDetector ?? new FormRequestTypeDetector();
        $this->factory = $factory ?? new FormRequestFactory(new ServerRequestFactory());
        $this->parameterResolver = $parameterResolver ?? new ParameterResolver();

        $this->class = $class;
        $this->method = $method;
    }

    public function beforeResolution(ServerRequestInterface $request, callable $callable): ServerRequestInterface
    {
        $reflection = new ReflectionMethod($this->class, $this->method);
        $formRequestClass = $this->typeDetector->detect($reflection);

        if ($formRequestClass) {
            $formRequest = $this->factory->create($formRequestClass, $request);
            $formRequest->populateProperties();
            $formRequest->validate();

            // Add validated FormRequest to attributes (will be picked up by ParameterResolver)
            foreach ($reflection->getParameters() as $param) {
                $type = $param->getType();
                if ($type instanceof \ReflectionNamedType && $type->getName() === $formRequestClass) {
                    $request = $request->withAttribute($param->getName(), $formRequest);
                    break;
                }
            }
        }

        return $request;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $instance = $this->container->get($this->class);
        $reflection = new \ReflectionMethod($this->class, $this->method);
        $callable = [$instance, $this->method];

        // ParameterResolver will call beforeResolution() hook automatically
        /** @phpstan-ignore-next-line */
        $arguments = $this->parameterResolver->resolve($reflection, $request, $this, $callable);

        return $instance->{$this->method}(...$arguments);
    }
}
