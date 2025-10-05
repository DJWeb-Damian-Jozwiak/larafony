<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\ServiceProviders;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\ErrorHandler\DetailedErrorHandler;

class ErrorHandlerServiceProvider extends ServiceProvider
{
    public array $providers {
        get => [DetailedErrorHandler::class];
    }

    #[\Override]
    public function register(ContainerContract $container): self
    {
        parent::register($container);
        return $this;
    }

    public function boot(ContainerContract $container): void
    {
        /**
         * @var DetailedErrorHandler $item
         */
        $item = $container->get(DetailedErrorHandler::class);
        $item->register();
    }


}