<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Container\Contracts\ContainerContract;

#[AsCommand(name: 'table:auth-role-permission')]
class AuthRolePermissionTable extends MakeMigration
{
    public function __construct(OutputContract $output, protected ContainerContract $container)
    {
        parent::__construct($output, $container);
        $this->name = ClockFactory::now()->format('Y_m_d_His_') . 'create_role_permissions_table.php';
    }

    protected function getStub(): string
    {
        $stubPath = dirname(__DIR__, 4) . '/stubs/auth_role_permissions_migration.stub';

        return file_get_contents($stubPath);
    }

    public function run(): int
    {
        return $this->buildFromName($this->name);
    }
}
