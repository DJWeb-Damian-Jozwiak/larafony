<?php

declare(strict_types=1);

namespace Larafony\Framework\Web;

use Larafony\Framework\Container\Container;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\Contracts\ServiceProviderContract;
use Larafony\Framework\Http\Factories\ServerRequestFactory;

final class Application extends Container
{
    protected static ?self $instance = null;
    protected function __construct(public private(set) readonly ?string $base_path = null)
    {
        parent::__construct();
        $this->set(ContainerContract::class, $this);
        $this->bind('base_path', $this->base_path);
    }

    public static function empty(): void
    {
        self::$instance = null;
    }

    public static function instance(?string $base_path = null): Application
    {
        self::$instance ??= new self($base_path);
        return self::$instance;
    }

    public function withRoutes(callable $callback): self
    {
        $kernel = $this->get(Kernel::class);
        $kernel->withRoutes($callback);
        return $this;
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

    public function run(?callable $exitCallback = null): void
    {
        $kernel = $this->get(Kernel::class);
        $request = $this->get(ServerRequestFactory::class)->createServerRequestFromGlobals();
        $response = $kernel->handle($request, $exitCallback);
        echo $response->getBody()->getContents();
    }
}
