<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands;

use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Console\Attributes\AsCommand;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Core\EnvFileHandler;
use Larafony\Framework\Database\DatabaseManager;

#[AsCommand('database:connect')]
class ConnectToDatabase extends Command
{
    public function run(): int
    {
        $config = $this->container->get(ConfigContract::class);
        $params = (array) $config->get('database.connections');
        try {
            new DatabaseManager($params)->connection()->connect();
            $this->output->success('Already connected to database');
            return 0; // No changes needed
        } catch (\Throwable $e) {
            $credentialsUpdated = $this->tryConnect();
            return $credentialsUpdated ? 2 : 0; // 2 = credentials updated, reload needed
        }
    }

    private function tryConnect(): bool
    {
        $this->output->warning('Configure database connection');

        $defaults = [
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'larafony',
            'username' => 'root',
            'password' => '',
        ];

        $connected = false;
        $config = $this->container->get(ConfigContract::class);
        $connection = $config->get('database.default');
        $params = (array) $config->get('database.connections');

        do {
            try {
                foreach ($defaults as $key => $default) {
                    $prompt = "Enter {$key}" . ($default !== '' ? " [{$default}]" : '') . ': ';

                    if ($key === 'password') {
                        $value = $this->output->secret($prompt);
                        // Use default if empty
                        if ($value === '') {
                            $value = $default;
                        }
                    } else {
                        $value = $this->output->question($prompt, (string) $default);
                    }

                    // Validation
                    if ($key === 'port' && ! is_numeric($value)) {
                        $this->output->error('Port must be numeric');
                        continue 2; // Restart whole loop
                    }

                    if ($key === 'port') {
                        $value = (int) $value;
                    }

                    // Update env with DB_ prefix and params
                    $envKey = 'DB_' . strtoupper($key);
                    new EnvFileHandler()->update($envKey, $value);
                    $params[$connection][$key] = $value;
                }

                // Test connection
                new DatabaseManager($params)->connection()->connect();
                $connected = true;
                $this->output->success('Database connected successfully!');
            } catch (\PDOException $e) {
                $this->output->error("Connection failed: {$e->getMessage()}");
                $this->output->info('Please try again with correct credentials...');
            } catch (\Throwable $e) {
                $this->output->error("Unexpected error: {$e->getMessage()}");
                $this->output->info('Please try again...');
            }
        } while (! $connected);

        return true; // Credentials were updated
    }
}
