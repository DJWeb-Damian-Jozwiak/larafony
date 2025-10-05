<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Container;

use Larafony\Framework\Container\Attributes\Deferred;
use Larafony\Framework\Container\Container;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use PHPUnit\Framework\TestCase;

final class DeferredServiceTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testDeferredProviderIsNotLoadedImmediately(): void
    {
        $this->container->registerDeferred(DeferredTestProvider::class);

        // Provider should not be loaded yet
        $this->assertFalse(
            $this->container->deferredLoader?->isProviderLoaded(DeferredTestProvider::class)
        );
    }

    public function testDeferredProviderIsLoadedWhenServiceRequested(): void
    {
        $this->container->registerDeferred(DeferredTestProvider::class);

        // Request the service
        $service = $this->container->get(DeferredTestService::class);

        $this->assertInstanceOf(DeferredTestService::class, $service);
        $this->assertTrue(
            $this->container->deferredLoader?->isProviderLoaded(DeferredTestProvider::class)
        );
    }

    public function testDeferredServiceIsDetectedByHas(): void
    {
        $this->container->registerDeferred(DeferredTestProvider::class);

        $this->assertTrue($this->container->has(DeferredTestService::class));
    }

    public function testDeferredServiceIsCachedAsSingleton(): void
    {
        $this->container->registerDeferred(DeferredTestProvider::class);

        $instance1 = $this->container->get(DeferredTestService::class);
        $instance2 = $this->container->get(DeferredTestService::class);

        // All deferred services are cached as singletons
        $this->assertSame($instance1, $instance2);
    }

    public function testDeferredProviderBootIsCalledOnce(): void
    {
        $this->container->registerDeferred(DeferredBootTrackingProvider::class);

        // Reset boot counter
        DeferredBootTrackingProvider::$bootCount = 0;

        // Request service multiple times
        $s1 = $this->container->get(DeferredTestService::class);
        $s2 = $this->container->get(DeferredTestService::class);
        $s3 = $this->container->get(DeferredTestService::class);

        // Boot should only be called once
        $this->assertSame(1, DeferredBootTrackingProvider::$bootCount);
        $this->assertInstanceOf(DeferredTestService::class, $s1);
    }

    public function testMultipleDeferredServicesFromSameProvider(): void
    {
        $this->container->registerDeferred(MultiServiceDeferredProvider::class);

        $service1 = $this->container->get(DeferredTestService::class);
        $service2 = $this->container->get(DeferredNonSingletonService::class);

        $this->assertInstanceOf(DeferredTestService::class, $service1);
        $this->assertInstanceOf(DeferredNonSingletonService::class, $service2);

        // Should only load provider once
        $this->assertTrue(
            $this->container->deferredLoader?->isProviderLoaded(MultiServiceDeferredProvider::class)
        );
    }

    public function testDeferredLoaderListsServices(): void
    {
        $this->container->registerDeferred(DeferredTestProvider::class);

        $services = $this->container->deferredLoader?->getDeferredServices();

        $this->assertContains(DeferredTestService::class, $services);
    }

    public function testNonDeferredProviderWorksNormally(): void
    {
        $provider = new NonDeferredProvider();
        $provider->register($this->container)->boot($this->container);

        $service = $this->container->get(DeferredTestService::class);

        $this->assertInstanceOf(DeferredTestService::class, $service);
    }
}

// Test helpers
class DeferredTestService
{
}

class DeferredNonSingletonService
{
}

#[Deferred([DeferredTestService::class])]
class DeferredTestProvider extends ServiceProvider
{
    public array $providers {
        get => [DeferredTestService::class, 'deferred' => DeferredTestService::class];
    }

    public function boot(ContainerContract $container): void {}
}

#[Deferred([DeferredTestService::class])]
class DeferredBootTrackingProvider extends ServiceProvider
{
    public static int $bootCount = 0;

    public array $providers {
        get => [DeferredTestService::class];
    }

    public function boot(ContainerContract $container): void
    {
        self::$bootCount++;
    }
}

#[Deferred([DeferredTestService::class, DeferredNonSingletonService::class])]
class MultiServiceDeferredProvider extends ServiceProvider
{
    public array $providers {
        get => [
            DeferredTestService::class,
            DeferredNonSingletonService::class,
        ];
    }

    public function boot(ContainerContract $container): void {}
}

class NonDeferredProvider extends ServiceProvider
{
    public array $providers {
        get => [DeferredTestService::class];
    }

    public function boot(ContainerContract $container): void {}
}
