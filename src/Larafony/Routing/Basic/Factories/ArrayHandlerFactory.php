<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Basic\Factories;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Validation\Handlers\FormRequestAwareHandler;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class ArrayHandlerFactory
{
    public function __construct(
        private ContainerContract $container,
    ) {
    }

    /**
     * @param array<mixed> $handler
     */
    public function create(array $handler): RequestHandlerInterface
    {
        if (count($handler) !== 2) {
            throw new \InvalidArgumentException(
                'Array handler must contain exactly 2 elements: [class, method]'
            );
        }

        [$class, $method] = $handler;

        if (! is_string($class) || ! is_string($method)) {
            throw new \InvalidArgumentException(
                'Array handler must contain strings: [class, method]'
            );
        }

        // Use FormRequestAwareHandler to support both ServerRequest and FormRequest DTOs
        return new FormRequestAwareHandler($class, $method, $this->container);
    }
}
