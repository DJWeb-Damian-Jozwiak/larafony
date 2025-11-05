<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Container\Contracts\ContainerContract;

#[AsCommand(name: 'table:session')]
class SessionTable extends MakeMigration
{
    public function __construct(OutputContract $output, protected ContainerContract $container)
    {
        parent::__construct($output, $container);
        $this->name = ClockFactory::now()->format('Y_m_d_His_') . 'create_sessions_table.php';
    }

    protected function getStub(): string
    {
        $stubPath = dirname(__DIR__, 4) . '/stubs/session_migration.stub';

        return file_get_contents($stubPath);
    }

    public function run(): int
    {
        return $this->buildFromName($this->name);
    }
}
