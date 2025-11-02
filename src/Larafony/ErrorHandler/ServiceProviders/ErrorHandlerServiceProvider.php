<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\ServiceProviders;

use Larafony\Framework\Config\Environment\EnvReader;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\ErrorHandler\BaseHandler;
use Larafony\Framework\ErrorHandler\DetailedErrorHandler;
use Larafony\Framework\ErrorHandler\Handlers\ConsoleHandler;
use Larafony\Framework\ErrorHandler\Renderers\Partials\ConsoleRendererFactory;
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

        $debug = EnvReader::read('APP_DEBUG', 'false') === 'true';

        // Determine if we're running in console mode
        $isConsole = php_sapi_name() === 'cli';

        if ($isConsole) {
            // Register console error handler
            $factory = new ConsoleRendererFactory($container);
            $renderer = $factory->create();

            $handler = new ConsoleHandler(
                $renderer,
                static fn (int $exitCode) => exit($exitCode)
            );

            $container->set(BaseHandler::class, $handler);
            $container->set(ConsoleHandler::class, $handler);
        } else {
            // Register web error handler with ViewManager and debug flag
            $viewManager = $container->get(ViewManager::class);

            $handler = new DetailedErrorHandler($viewManager, $debug);

            $container->set(BaseHandler::class, $handler);
            $container->set(DetailedErrorHandler::class, $handler);
        }

        return $this;
    }

    public function boot(ContainerContract $container): void
    {
        /**
         * @var BaseHandler $handler
         */
        $handler = $container->get(BaseHandler::class);
        $handler->register();
    }
}
