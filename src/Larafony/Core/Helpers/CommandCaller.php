<?php

declare(strict_types=1);

namespace Larafony\Framework\Core\Helpers;

use Larafony\Framework\Console\CommandRegistry;
use Larafony\Framework\Console\CommandResolver;
use Larafony\Framework\Console\Exceptions\CommandNotFoundError;
use Larafony\Framework\Console\Input\Input;
use Larafony\Framework\Container\Contracts\ContainerContract;

/**
 * Helper class for programmatically calling console commands.
 *
 * Similar to Laravel's Artisan::call(), this allows executing
 * commands from within the application code.
 *
 * @example
 * ```php
 * $caller = new CommandCaller($container, $registry);
 * $exitCode = $caller->call('migrate', ['--force' => true]);
 * ```
 */
class CommandCaller
{
    public function __construct(
        private readonly ContainerContract $container,
        private readonly CommandRegistry $registry,
    ) {
    }

    /**
     * Call a console command by name.
     *
     * @param string $command Command name (e.g., 'migrate', 'cache:clear')
     * @param array<int|string, mixed> $arguments Command arguments (e.g., ['name' => 'value'] or ['value'])
     * @param array<string, mixed> $options Command options (e.g., ['--force' => true, '-v' => true])
     *
     * @return int Exit code (0 for success, non-zero for failure)
     *
     * @throws CommandNotFoundError If command is not found in registry
     */
    public function call(string $command, array $arguments = [], array $options = []): int
    {
        $input = new Input(
            $command,
            array_map(strval(...), array_values($arguments)),
            $this->formatOptions($options)
        );

        $commandClass = $this->registry->get($input->command);
        $commandInstance = $this->container->get($commandClass);
        new CommandResolver($commandInstance, $input)->resolve();

        return $commandInstance->run();
    }

    /**
     * Format options array to match Input expectations.
     *
     * @param array<string, mixed> $options
     *
     * @return array<int, string>
     */
    private function formatOptions(array $options): array
    {
        $formatted = [];
        foreach ($options as $key => $value) {
            if ($value === true) {
                $formatted[] = $key;
            } elseif (is_string($value) || is_numeric($value)) {
                $formatted[] = "{$key}={$value}";
            }
        }
        return $formatted;
    }
}
