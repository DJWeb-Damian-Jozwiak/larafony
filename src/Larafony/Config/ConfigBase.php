<?php

declare(strict_types=1);

namespace Larafony\Framework\Config;

use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Config\Environment\EnvironmentLoader;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\Helpers\DotContainer;

class ConfigBase extends DotContainer implements ConfigContract
{
    private bool $loaded = false;
    public function __construct(private readonly ContainerContract $app)
    {
        parent::__construct();
    }

    public function loadConfig(): void
    {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;
        $this->loadEnvironmentVariables();
        $this->loadConfigFiles();
        $this->app->set(ConfigContract::class, $this);
    }

    private function loadEnvironmentVariables(): void
    {
        $envPath = $this->app->base_path . '/.env';
        $this->app->get(EnvironmentLoader::class)->load($envPath);
    }

    private function loadConfigFiles(): void
    {
        $configPath = $this->app->base_path . DIRECTORY_SEPARATOR . 'config';

        if (! is_dir($configPath)) {
            return;
        }

        $files = scandir($configPath) ?: [];
        $files = array_filter(
            $files,
            static fn ($file) => pathinfo($file, PATHINFO_EXTENSION) === 'php'
        );

        foreach ($files as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            $this->set(
                $key,
                require $configPath . DIRECTORY_SEPARATOR . $file
            );
        }
    }
}
