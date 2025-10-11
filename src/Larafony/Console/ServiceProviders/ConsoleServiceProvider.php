<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\ServiceProviders;

use Larafony\Framework\Console\CommandCache;
use Larafony\Framework\Console\CommandDiscovery;
use Larafony\Framework\Console\CommandRegistry;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Console\Formatters\OutputFormatter;
use Larafony\Framework\Console\Formatters\Styles\DangerStyle;
use Larafony\Framework\Console\Formatters\Styles\InfoStyle;
use Larafony\Framework\Console\Formatters\Styles\SuccessStyle;
use Larafony\Framework\Console\Formatters\Styles\WarningStyle;
use Larafony\Framework\Console\Output;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Psr\Http\Message\StreamFactoryInterface;

class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, class-string> $providers
     */
    public array $providers {
        get => [
            OutputFormatter::class => OutputFormatter::class,
        ];
    }

    public function register(ContainerContract $container): self
    {
        parent::register($container);

        $factory = $container->get(StreamFactoryInterface::class);
        $container->set('input_stream', $factory->createStreamFromFile('php://stdin'));
        $container->set('output_stream', $factory->createStreamFromFile('php://stdout'));

        // Register Output after streams are available
        $container->set(OutputContract::class, Output::class);

        return $this;
    }

    public function boot(ContainerContract $container): void
    {
        $formatter = $container->get(OutputFormatter::class);

        $formatter->withStyle('danger', new DangerStyle());
        $formatter->withStyle('info', new InfoStyle());
        $formatter->withStyle('success', new SuccessStyle());
        $formatter->withStyle('warning', new WarningStyle());

        $registry = $container->get(CommandRegistry::class);

        // Discover and register framework commands
        $discovery = new CommandDiscovery();
        $commandsDir = __DIR__ . '/../Commands';
        $discovery->discover($commandsDir, 'Larafony\\Framework\\Console\\Commands');
        foreach ($discovery->commands as $name => $class) {
            $registry->register($name, $class);
        }
    }
}
