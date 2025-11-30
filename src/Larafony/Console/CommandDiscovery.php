<?php

declare(strict_types=1);

namespace Larafony\Framework\Console;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Core\Helpers\Directory;
use Larafony\Framework\Core\Support\FileToClassNameConverter;

final class CommandDiscovery
{
    /**
     * @var array<string, class-string<Command>>
     */
    public private(set) array $commands;

    /**
     * @param array<string, class-string<Command>> $commands
     */
    public function __construct(array $commands = [])
    {
        $this->commands = $commands;
    }

    /**
     * Register a command manually
     *
     * @param string $name Command name (e.g., 'database:seed')
     * @param class-string<Command> $commandClass
     */
    public function register(string $name, string $commandClass): void
    {
        $this->commands[$name] = $commandClass;
    }

    /**
     * Discover commands from directory
     *
     * @param string $directoryName Directory to scan for commands
     * @param string $namespace Base namespace for commands
     */
    public function discover(string $directoryName, string $namespace): void
    {
        $files = new Directory($directoryName)->files;
        $files = array_filter($files, static fn (\SplFileInfo $file) => $file->getExtension() === 'php');
        /** @var array<int, class-string<Command>> $classes */
        $classes = array_map(
            static fn (\SplFileInfo $file) => new FileToClassNameConverter()
                ->convert($file, $directoryName, $namespace),
            $files,
        ) |> (static fn (array $classes) => array_filter($classes, static fn (string $class) => class_exists($class)));
        foreach ($classes as $class) {
            $this->handleSingleCommand($class);
        }
    }

    /**
     * @param class-string<Command> $className
     */
    private function handleSingleCommand(string $className): void
    {
        $reflection = new \ReflectionClass($className);
        if ($reflection->isAbstract()) {
            return;
        }
        $attributes = $reflection->getAttributes(AsCommand::class);
        if (! $attributes) {
            return;
        }
        foreach ($attributes as $attribute) {
            $attribute = $attribute->newInstance();
            $this->register($attribute->name, $className);
        }
    }
}
