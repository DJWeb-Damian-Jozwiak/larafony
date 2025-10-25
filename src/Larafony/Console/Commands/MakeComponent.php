<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Core\Helpers\FileSystem;
use Larafony\Framework\Web\Application;

#[AsCommand(name: 'make:component')]
class MakeComponent extends MakeCommand
{
    public function run(): int
    {
        $exitCode = parent::run();

        if ($exitCode === 0) {
            $this->createBladeView();
        }

        return $exitCode;
    }

    protected function createBladeView(): void
    {
        $viewName = $this->getViewName();
        $viewPath = $this->getViewPath();
        $directory = dirname($viewPath);

        $this->output->info("Creating blade view...");
        $this->output->info("View path: {$viewPath}");
        $this->output->info("Directory: {$directory}");

        // Ensure directory exists
        if (! is_dir($directory)) {
            $this->output->info("Creating directory: {$directory}");
        }
        FileSystem::createDirectoryIfMissing($directory);

        $stub = FileSystem::tryGetFileContent($this->getBladeStub());
        $stub = str_replace('DummyKebabCase', $this->toKebabCase($this->getClassName()), $stub);

        file_put_contents($viewPath, $stub);

        $this->output->success("Created view: {$viewName}");
    }

    protected function getViewName(): string
    {
        $className = $this->getClassName();
        return 'components.' . $className . '.blade.php';
    }

    protected function getClassName(): string
    {
        $name = $this->name;
        return basename(str_replace('\\', '/', $name));
    }

    protected function getViewPath(): string
    {
        $app = Application::instance();
        $basePath = $app->base_path ?? getcwd();
        $viewsPath = $basePath . '/resources/views';
        $className = $this->getClassName();

        return $viewsPath . '/components/' . $className . '.blade.php';
    }

    protected function toKebabCase(string $string): string
    {
        $result = preg_replace('/(?<!^)[A-Z]/', '-$0', $string);
        return strtolower($result ?? $string);
    }

    protected function getStub(): string
    {
        $dir = dirname(__DIR__, 4);
        return $dir . '/stubs/component.stub';
    }

    protected function getBladeStub(): string
    {
        $dir = dirname(__DIR__, 4);
        return $dir . '/stubs/component.blade.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return 'App\\View\\Components';
    }

    protected function getPath(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        $path = 'src/View/Components/';

        return $path . basename($name);
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
        $this->replaceViewName($stub);

        return $stub;
    }

    protected function replaceViewName(string &$stub): self
    {
        $className = $this->getClassName();
        $stub = str_replace('DummyView', $className, $stub);
        return $this;
    }
}
