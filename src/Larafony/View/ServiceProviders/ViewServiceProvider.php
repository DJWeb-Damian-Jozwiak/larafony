<?php

declare(strict_types=1);

namespace Larafony\Framework\View\ServiceProviders;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\View\Contracts\RendererContract;
use Larafony\Framework\View\Engines\BladeAdapter;
use Larafony\Framework\View\ViewManager;

class ViewServiceProvider extends ServiceProvider
{
    public function register(ContainerContract $container): self
    {
        // Register Blade renderer as default
        $container->set(
            RendererContract::class,
            BladeAdapter::buildDefault()
        );

        // Register ViewManager with default renderer
        $container->set(
            ViewManager::class,
            new ViewManager($container->get(RendererContract::class))
        );

        // Register named renderers for easy switching (future: Twig, etc.)
        $container->set('view.renderer.blade', BladeAdapter::buildDefault());

        return $this;
    }

    public function boot(ContainerContract $container): void
    {
        // Boot logic can be added here if needed in future
        // For example: loading view composers, sharing data, etc.
    }
}
