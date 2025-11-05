<?php

declare(strict_types=1);

namespace Larafony\Framework\Console;

use Larafony\Framework\Console\Exceptions\CommandNotFoundError;
use Larafony\Framework\Container\Container;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\Contracts\ServiceProviderContract;

class Application extends Container
{
    protected static ?self $instance = null;
    private Kernel $kernel;
    protected function __construct(public private(set) ?string $base_path = null)
    {
        parent::__construct();
        $this->set(ContainerContract::class, $this);
        $this->bind('base_path', $this->base_path);

        $registry = new CommandRegistry();
        $this->set(CommandRegistry::class, $registry);

        $this->kernel = new Kernel($this->base_path ?? '', $registry, $this);
        $this->set(Kernel::class, $this->kernel);
    }

    public static function empty(): void
    {
        self::$instance = null;
    }

    public static function instance(?string $base_path = null): self
    {
        self::$instance ??= new self($base_path);
        return self::$instance;
    }

    public function handle(?array $args = null): int
    {
        $args ??= $_SERVER['argv'];
        try {
            return $this->kernel->handle($args);
        } catch (CommandNotFoundError) {
            $output = $this->get(Output::class);
            $output->error('Command not found');
            return 1;
        }
    }

    /**
     * @param array<int, class-string<ServiceProviderContract>> $serviceProviders
     */
    public function withServiceProviders(array $serviceProviders): self
    {
        array_walk(
            $serviceProviders,
            fn (string $provider) => $this->get($provider)->register($this)->boot($this),
        );
        return $this;
    }
}
