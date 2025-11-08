<?php

declare(strict_types=1);

namespace Larafony\Framework\Auth\ServiceProviders;

use Larafony\Framework\Auth\Auth;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(ContainerContract $container): void
    {
        parent::boot($container);

        // Set container for Auth facade - DI handles the rest automatically
        Auth::withContainer($container);
    }
}
