<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Handlers;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
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
final class FormRequestAwareHandler implements RequestHandlerInterface
{
    private string $class;
    private string $method;
    private FormRequestTypeDetector $typeDetector;
    private FormRequestFactory $factory;

    public function __construct(
        string $class,
        string $method,
        private readonly ContainerContract $container,
        ?FormRequestTypeDetector $typeDetector = null,
        ?FormRequestFactory $factory = null,
    ) {
        $this->typeDetector = $typeDetector ?? new FormRequestTypeDetector();
        $this->factory = $factory ?? new FormRequestFactory(new ServerRequestFactory());
        if (! class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('Class %s does not exist', $class));
        }

        if (! method_exists($class, $method)) {
            throw new \InvalidArgumentException(
                sprintf('Method %s does not exist in class %s', $method, $class)
            );
        }

        $this->class = $class;
        $this->method = $method;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $instance = $this->container->get($this->class);
        $reflection = new ReflectionMethod($this->class, $this->method);

        $formRequestClass = $this->typeDetector->detect($reflection);

        if (! $formRequestClass) {
            return $instance->{$this->method}($request);
        }

        $formRequest = $this->factory->create($formRequestClass, $request);
        $formRequest->populateProperties();
        $formRequest->validate();

        return $instance->{$this->method}($formRequest);
    }
}
