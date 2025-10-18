<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Console\Attributes\AsCommand;

#[AsCommand(name: 'make:migration')]
class MakeMigration extends MakeCommand
{
    protected function getStub(): string
    {
        $dir = dirname(__DIR__, 4);

        return $dir . '/stubs/migration.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return 'App\\Database\\Migrations';
    }

    protected function getPath(string $name): string
    {
        $name = str_replace('\\', '/', $name);

        $default = 'database/migrations/';
        $path = $this->container->get(ConfigContract::class)->get('database.migrations.path', $default);

        return $path . '/' . $name;
    }

    protected function qualifyName(string $name): string
    {
        if (! str_contains($name, '_table')) {
            $name .= '_table';
        }

        $name = parent::qualifyName($name);

        return ClockFactory::now()->format('Y_m_d_His_') . $name;
    }
}
