<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\ServiceProviders;

use Larafony\Framework\Config\Environment\EnvReader;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\ErrorHandler\DetailedErrorHandler;
use Larafony\Framework\View\ViewManager;

class ErrorHandlerServiceProvider extends ServiceProvider
{
    /**
     * @return  array<int|string, class-string>
     */
    public function providers(): array
    {
        return [];
    }

    #[\Override]
    public function register(ContainerContract $container): self
    {
        parent::register($container);

        // Register error handler with ViewManager and debug flag
        $debug = EnvReader::read('APP_DEBUG', 'false') === 'true';
        $viewManager = $container->get(ViewManager::class);

        $container->set(
            DetailedErrorHandler::class,
            new DetailedErrorHandler($viewManager, $debug)
        );

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
