<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Command;

#[AsCommand(name: 'table:session')]
class SessionTable extends Command
{
    public function run(): int
    {
        $migrationName = ClockFactory::now()->format('Y_m_d_His_') . 'create_sessions_table.php';

        $default = 'database/migrations/';
        $path = $this->container->get(ConfigContract::class)->get('database.migrations.path', $default);

        $fullPath = $path . '/' . $migrationName;

        $stub = $this->getStub();
        $content = str_replace('DummyRootNamespace', 'App\\Database\\Migrations', $stub);

        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        file_put_contents($fullPath, $content);

        $this->output->success("Migration created successfully: {$migrationName}");

        return 0;
    }

    protected function getStub(): string
    {
        $stubPath = dirname(__DIR__, 4) . '/stubs/session_migration.stub';

        return file_get_contents($stubPath);
    }
}
