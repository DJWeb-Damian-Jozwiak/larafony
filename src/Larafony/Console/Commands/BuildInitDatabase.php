<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Command;

#[AsCommand('database:init')]
class BuildInitDatabase extends Command
{
    /** @var array<int, string> $commands */
    private array $commands = [
        'table:database-log',
        'table:session',
        'table:mail-log',
        'table:auth-permission',
        'table:auth-role',
        'table:auth-user',
        'table:auth-role-permission',
        'table:auth-user-role',
        'migrate:fresh',
    ];
    public function run(): int
    {
        $this->output->info('Initializing database...');

        $exitCode = $this->call('database:connect');

        if ($exitCode === 2) {
            // Credentials were updated, need to reload and restart
            $this->output->info('Configuration updated. Restarting with new credentials...');
            return $this->restartWithNewConfig();
        }

        // Continue with initialization
        $this->migrationTables();
        $this->call('migrate:fresh');
        $this->output->success('Database initialized successfully!');

        return 0;
    }

    private function migrationTables(): void
    {
        $this->call('table:database-log');
        $this->call('table:session');
        $this->call('table:mail-log');
        $this->call('table:auth-permission');
        $this->call('table:auth-role');
        $this->call('table:auth-user');
        $this->call('table:auth-role-permission');
        $this->call('table:auth-user-role');
    }

    private function restartWithNewConfig(): int
    {
        // Execute the init-tables part in a new process with fresh config
        $this->output->info('Running database initialization...');

        foreach ($this->commands as $command) {
            // Use passthru to show output in real-time
            $commandLine = 'php8.5 bin/larafony ' . escapeshellarg($command);
            passthru($commandLine, $result);

            if ($result !== 0) {
                $this->output->error("Command '{$command}' failed with exit code {$result}");
                return $result;
            }
        }

        $this->output->success('Database initialized successfully!');
        return 0;
    }
}
