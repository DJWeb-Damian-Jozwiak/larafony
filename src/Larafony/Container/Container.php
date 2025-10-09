<?php

declare(strict_types=1);

namespace Larafony\Framework\Container;

use Larafony\Framework\Container\Contracts\ArrayContract;
use Larafony\Framework\Container\Contracts\AutowireContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\Exceptions\NotFoundError;
use Larafony\Framework\Container\Helpers\DotContainer;
use Larafony\Framework\Container\Resolvers\Autowire;
use Larafony\Framework\Core\Support\Str;
use NoDiscard;

class Container implements ContainerContract
{
    public private(set) DeferredServiceLoader $deferredLoader;
    /**
     * @var array<string, string|int|float|bool|null>
     */
    private array $bindings = [];

    protected function __construct(
        private ?AutowireContract $autowire = null,
        private readonly ArrayContract $entries = new DotContainer(),
    ) {
        $this->autowire ??= new Autowire($this);
        $this->deferredLoader = new DeferredServiceLoader($this);
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
        if (Str::isClassString($value) && ! $this->has($value)) {
            $value = $this->autowire?->instantiate($value);
        }
        $this->entries->set($key, $value);
        return $this;
    }

    /**
     * @param class-string $id
     */
    #[NoDiscard]
    public function get(string $id): mixed
    {
        // Check deferred cache first (since isDeferred returns false after loading)
        $cached = $this->deferredLoader->getCached($id);
        if ($cached !== null) {
            return $cached;
        }

        if ($this->deferredLoader->isDeferred($id)) {
            /** @phpstan-ignore-next-line */
            return $this->deferredLoader->load($id);
        }

        if (! $this->entries->has($id)) {
            return $this->autowire?->instantiate($id);
        }

        $value = $this->entries->get($id);

        return Str::isClassString($value) ? $this->autowire?->instantiate($value) : $value;
    }

    public function has(string $id): bool
    {
        return $this->deferredLoader->isDeferred($id) || $this->entries->has($id);
    }

    /**
     * Register a deferred service provider.
     *
     * @param class-string<\Larafony\Framework\Container\Contracts\ServiceProviderContract> $providerClass
     */
    public function registerDeferred(string $providerClass): self
    {
        $this->deferredLoader->registerDeferred($providerClass);
        return $this;
    }
}
