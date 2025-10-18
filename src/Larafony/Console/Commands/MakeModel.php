<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Attributes\CommandOption;
use Larafony\Framework\Core\Helpers\CommandCaller;
use Larafony\Framework\Core\Support\Str;

#[AsCommand(name: 'make:model')]
class MakeModel extends MakeCommand
{
    #[CommandOption(name: 'migration', description: 'Create migration file for the model')]
    protected bool $migration = false;

    private CommandCaller $commandCaller;

    public function __construct(
        \Larafony\Framework\Console\Contracts\OutputContract $output,
        \Larafony\Framework\Container\Contracts\ContainerContract $container,
        CommandCaller $commandCaller,
    ) {
        parent::__construct($output, $container);
        $this->commandCaller = $commandCaller;
    }

    public function run(): int
    {
        $exitCode = parent::run();

        if ($exitCode === 0 && $this->migration) {
            $this->createMigration();
        }

        return $exitCode;
    }

    protected function createMigration(): void
    {
        $tableName = $this->getTableName();
        $migrationName = "create_{$tableName}_table";

        $this->output->info("Creating migration for table: {$tableName}");

        $this->commandCaller->call('make:migration', [0 => $migrationName]);
    }

    protected function getTableName(): string
    {
        $modelName = $this->name;
        $modelName = basename(str_replace('\\', '/', $modelName));

        // Convert PascalCase to snake_case and pluralize
        $snakeCased = preg_replace('/(?<!^)[A-Z]/', '_$0', $modelName);
        return strtolower($snakeCased ?? $modelName) |> Str::pluralize(...);
    }

    protected function getStub(): string
    {
        $dir = dirname(__DIR__, 4);
        return $dir . '/stubs/model.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return 'App\\Models';
    }

    protected function getPath(string $name): string
    {
        $name = str_replace('\\', '/', $name);

        $default = 'src/Models/';
        $path = $this->container->get(ConfigContract::class)->get('app.models.path', $default);

        return $path . $name;
    }

    protected function qualifyName(string $name): string
    {
        $name = ltrim($name, '\\/');
        $name = str_replace('/', '\\', $name);

        // Don't add namespace if already fully qualified
        if (! str_contains($name, '\\')) {
            $name = $this->getDefaultNamespace() . '\\' . $name;
        }

        return $name . '.php';
    }

    protected function buildClass(string $name): string
    {
        $stub = file_get_contents($this->getStub());
        $stub = $stub ? $stub : '';

        $this->replaceNamespace($stub, $name);
        $stub = $this->replaceClass($stub, $name);
        $this->replaceTable($stub);

        return $stub;
    }

    protected function replaceTable(string &$stub): self
    {
        $tableName = $this->getTableName();
        $stub = str_replace('DummyTable', $tableName, $stub);
        return $this;
    }
}
