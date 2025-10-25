<?php

declare(strict_types=1);

namespace Larafony\Framework\Web\ServiceProviders;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\Routing\Advanced\Router;
use Larafony\Framework\Web\Config;
use Larafony\Framework\Web\Kernel;
use Larafony\Framework\Web\Middleware\MiddlewareStack;

class WebServiceProvider extends ServiceProvider
{
    /**
     * @var array<int|string, class-string> $providers
     */
    public array $providers {
        get => [
            Kernel::class => Kernel::class,
        ];
    }

    public function boot(ContainerContract $container): void
    {
        // Register MiddlewareStack with middleware from config
        $router = $container->get(Router::class);
        $stack = new MiddlewareStack($router);

        // Load middleware from config
        $middlewareConfig = Config::get('middleware', [
            'before_global' => [],
            'global' => [],
            'after_global' => [],
        ]);

        // Add before_global middleware
        foreach ($middlewareConfig['before_global'] as $middlewareClass) {
            $stack->add($container->get($middlewareClass));
        }

        // Add global middleware
        foreach ($middlewareConfig['global'] as $middlewareClass) {
            $stack->add($container->get($middlewareClass));
        }

        // Add after_global middleware
        foreach ($middlewareConfig['after_global'] as $middlewareClass) {
            $stack->add($container->get($middlewareClass));
        }

        $container->set(MiddlewareStack::class, $stack);
    }
}
