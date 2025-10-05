<?php

declare(strict_types=1);

namespace Larafony\Framework\Container;

use Larafony\Framework\Container\Contracts\ArrayContract;
use Larafony\Framework\Container\Contracts\AutowireContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\Exceptions\NotFoundError;
use Larafony\Framework\Container\Helpers\DotContainer;
use Larafony\Framework\Container\Resolvers\Autowire;
use NoDiscard;

class Container implements ContainerContract
{
    /**
     * @var array<string, string|int|float|bool|null>
     */
    private array $bindings = [];
    public function __construct(
        private ?AutowireContract $autowire = null,
        private readonly ArrayContract $entries = new DotContainer(),
    ) {
        $this->autowire ??= new Autowire($this);
    }
    public function bind(string $key, float|bool|int|string|null $value): void
    {
        $this->bindings[$key] = $value;
    }

    #[NoDiscard]
    public function getBinding(string $key): string|int|float|bool|null
    {
        return $this->bindings[$key] ?? throw new NotFoundError('Binding not found: '.$key);
    }

    /**
     * Sets item in container
     *
     * @param string $key
     * @param mixed $value
     *
     * @return self
     */
    public function set(string $key, mixed $value): Contracts\ContainerContract
    {
        $this->entries->set($key, $value);
        return $this;
    }

    /**
     * @param class-string $id
     */
    #[NoDiscard]
    public function get(string $id): mixed
    {
        if (!$this->entries->has($id)) {
            return $this->autowire->instantiate($id);
        }

        $value = $this->entries->get($id);

        // If the stored value is a class-string, autowire it
        if (is_string($value) && class_exists($value)) {
            return $this->autowire->instantiate($value);
        }

        return $value;
    }

    public function has(string $id): bool
    {
        return $this->entries->has($id);
    }
}