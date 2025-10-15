<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Console\Attributes\CommandArgument;
use Larafony\Framework\Console\Attributes\CommandOption;
use Larafony\Framework\Console\Command;

abstract class MakeCommand extends Command
{
    #[CommandArgument(name: 'name', description: 'Nazwa tworzonego elementu')]
    protected string $name;

    #[CommandOption(name: 'force', description: 'Nadpisz istniejący plik')]
    protected bool $force = false;

    public function run(): int
    {
        $name = $this->qualifyName($this->name);

        $path = $this->getPath($name);

        file_put_contents($path, $this->buildClass($name));

        $this->output->info("Utworzono {$name}");

        return 0;
    }

    protected function qualifyName(string $name): string
    {
        $name = ltrim($name, '\\/');
        $name = str_replace('/', '\\', $name);

        return $name . '.php';
    }

    abstract protected function getDefaultNamespace(): string;
    abstract protected function getPath(string $name): string;

    protected function buildClass(string $name): string
    {
        $stub = file_get_contents($this->getStub());

        $stub = $stub ? $stub : '';

        return $this->replaceNamespace($stub, $name)->replaceClass(
            $stub,
            $name
        );
    }

    abstract protected function getStub(): string;

    protected function replaceClass(string $stub, string $name): string
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);
        $class = str_replace('.php', '', $class);

        return str_replace('DummyClass', $class, $stub);
    }

    protected function getNamespace(string $name): string
    {
        return trim(
            implode('\\', array_slice(explode('\\', $name), 0, -1)),
            '\\'
        );
    }

    protected function replaceNamespace(string &$stub, string $name): self
    {
        $stub = str_replace(
            ['DummyNamespace', 'DummyRootNamespace'],
            [$this->getNamespace($name), $this->getDefaultNamespace()],
            $stub
        );

        return $this;
    }
}
